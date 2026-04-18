<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RegisterAntiBotVerdict;
use App\Support\RegisterSecurityLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class RegisterAntiBotService
{
    private const string EXPECTED_ACTION = 'register';

    private const int MAX_FORM_AGE_SECONDS = 86400;

    /** Submit più rapido di questo (secondi) in modalità degradata captcha: blocco immediato. */
    private const float DEGRADED_FAST_SUBMIT_MAX_SECONDS = 1.5;

    public function evaluate(
        int $formRenderedAt,
        ?string $recaptchaToken,
        string $remoteIp,
        ?string $clientAcceptLanguage,
        ?string $clientTimezone,
        ?string $clientScreen,
        string $email,
        float $minSubmitSeconds,
    ): RegisterAntiBotVerdict {
        $emailNormalized = strtolower(trim($email));

        if ($this->isDisposableEmailDomain($emailNormalized)) {
            $domain = strtolower((string) Str::after($emailNormalized, '@'));
            RegisterSecurityLog::event('register_disposable_domain_blocked', [
                'ip' => $remoteIp,
                'domain' => $domain,
            ]);

            return RegisterAntiBotVerdict::Block;
        }

        $siteKey = trim((string) config('services.recaptcha.site_key', ''));
        $secretKey = trim((string) config('services.recaptcha.secret_key', ''));
        $hasSite = $siteKey !== '';
        $hasSecret = $secretKey !== '';

        if (! $hasSite && ! $hasSecret) {
            return $this->verdictFromRiskWithoutCaptcha(
                $formRenderedAt,
                $clientAcceptLanguage,
                $clientTimezone,
                $emailNormalized,
                $minSubmitSeconds,
                $remoteIp,
            );
        }

        if ($hasSite xor $hasSecret) {
            RegisterSecurityLog::event('register_captcha_misconfigured', [
                'ip' => $remoteIp,
                'has_site_key' => $hasSite,
                'has_secret_key' => $hasSecret,
            ]);

            return RegisterAntiBotVerdict::Block;
        }

        $token = trim((string) ($recaptchaToken ?? ''));
        if ($token === '') {
            RegisterSecurityLog::event('register_captcha_token_missing', [
                'ip' => $remoteIp,
            ]);

            return RegisterAntiBotVerdict::Block;
        }

        $payload = $this->verifyWithGoogle($secretKey, $token, $remoteIp);
        if ($payload === null) {
            RegisterSecurityLog::event('register_captcha_provider_unreachable', [
                'ip' => $remoteIp,
            ]);

            return $this->evaluateCaptchaProviderUnavailable(
                $formRenderedAt,
                $remoteIp,
            );
        }

        if (! ($payload['success'] ?? false)) {
            RegisterSecurityLog::event('register_captcha_verify_failed', [
                'ip' => $remoteIp,
                'reason' => 'success_false',
                'error_codes' => $payload['error-codes'] ?? $payload['error_codes'] ?? [],
            ]);

            return RegisterAntiBotVerdict::Block;
        }

        $action = (string) ($payload['action'] ?? '');
        if ($action !== self::EXPECTED_ACTION) {
            RegisterSecurityLog::event('register_captcha_action_mismatch', [
                'ip' => $remoteIp,
                'expected' => self::EXPECTED_ACTION,
                'actual' => $action,
            ]);

            return RegisterAntiBotVerdict::Block;
        }

        $score = array_key_exists('score', $payload) ? (float) $payload['score'] : -1.0;
        $band = $this->classifyScore($score);

        if ($band === 'block') {
            RegisterSecurityLog::event('register_captcha_score_block_band', [
                'ip' => $remoteIp,
                'score' => $score,
            ]);

            return RegisterAntiBotVerdict::Block;
        }

        $logSiteverifySuccess = app()->environment('local')
            || (bool) config('services.recaptcha.log_siteverify_success', false);
        if ($logSiteverifySuccess) {
            Log::info('register_recaptcha_siteverify_success', [
                'action' => $action,
                'score' => $score,
                'band' => $band,
            ]);
        }

        $risk = $this->riskFromCaptchaBand($band);
        $risk += $this->timingRisk($formRenderedAt, $minSubmitSeconds, $remoteIp);
        $risk += $this->fingerprintRisk($clientAcceptLanguage, $clientTimezone, $clientScreen);
        $risk += $this->emailEntropyRisk($emailNormalized);

        return $this->verdictFromRisk($risk, $band, $remoteIp);
    }

    private function isDisposableEmailDomain(string $emailLower): bool
    {
        $domain = strtolower((string) Str::after($emailLower, '@'));
        if ($domain === '') {
            return false;
        }

        /** @var list<string> $blocked */
        $blocked = config('registration.disposable_email_domains', []);

        return in_array($domain, $blocked, true);
    }

    private function evaluateCaptchaProviderUnavailable(
        int $formRenderedAt,
        string $remoteIp,
    ): RegisterAntiBotVerdict {
        $key = 'register-captcha-degraded:'.$remoteIp;

        if (RateLimiter::tooManyAttempts($key, 2)) {
            RegisterSecurityLog::event('register_captcha_degraded_rate_limited', [
                'ip' => $remoteIp,
            ]);

            return RegisterAntiBotVerdict::Block;
        }

        RateLimiter::hit($key, 60);

        $now = time();
        $elapsed = $now - $formRenderedAt;
        if ($elapsed >= 0 && $elapsed < self::DEGRADED_FAST_SUBMIT_MAX_SECONDS) {
            RegisterSecurityLog::event('register_captcha_degraded_fast_submit', [
                'ip' => $remoteIp,
                'elapsed_seconds' => $elapsed,
            ]);

            return RegisterAntiBotVerdict::Block;
        }

        return RegisterAntiBotVerdict::Pass;
    }

    private function verdictFromRiskWithoutCaptcha(
        int $formRenderedAt,
        ?string $clientAcceptLanguage,
        ?string $clientTimezone,
        string $emailLower,
        float $minSubmitSeconds,
        string $remoteIp,
    ): RegisterAntiBotVerdict {
        $risk = $this->timingRisk($formRenderedAt, $minSubmitSeconds, $remoteIp);
        $risk += $this->fingerprintRisk($clientAcceptLanguage, $clientTimezone, null);
        $risk += $this->emailEntropyRisk($emailLower);

        if ($risk >= 80) {
            RegisterSecurityLog::event('register_verdict_block', [
                'ip' => $remoteIp,
                'mode' => 'without_captcha',
                'risk' => $risk,
            ]);

            return RegisterAntiBotVerdict::Block;
        }

        if ($risk >= 40) {
            RegisterSecurityLog::event('register_verdict_challenge', [
                'ip' => $remoteIp,
                'mode' => 'without_captcha',
                'risk' => $risk,
            ]);

            return RegisterAntiBotVerdict::Challenge;
        }

        return RegisterAntiBotVerdict::Pass;
    }

    private function riskFromCaptchaBand(string $band): float
    {
        return match ($band) {
            'block' => 100.0,
            'challenge' => 40.0,
            'pass' => 0.0,
            default => 100.0,
        };
    }

    private function timingRisk(int $formRenderedAt, float $minSubmitSeconds, string $remoteIp): float
    {
        $now = time();
        if ($formRenderedAt > $now + 120) {
            return 100.0;
        }

        if ($now - $formRenderedAt > self::MAX_FORM_AGE_SECONDS) {
            return 40.0;
        }

        $elapsed = $now - $formRenderedAt;
        $threshold = max(1.5, min(3.0, $minSubmitSeconds));

        if ($elapsed >= 0 && $elapsed < $threshold) {
            RegisterSecurityLog::event('register_timing_under_threshold', [
                'ip' => $remoteIp,
                'elapsed_seconds' => $elapsed,
                'threshold_seconds' => $threshold,
            ]);

            return 25.0;
        }

        return 0.0;
    }

    /**
     * Segnale debole: local-part lunga con entropia elevata (non è un blocco diretto).
     */
    private function emailEntropyRisk(string $emailLower): float
    {
        $local = strstr($emailLower, '@', true);
        if ($local === false || $local === '') {
            return 0.0;
        }

        $len = strlen($local);
        if ($len < 24) {
            return 0.0;
        }

        $chars = preg_split('//u', $local, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false || $chars === []) {
            return 0.0;
        }

        $freq = array_count_values($chars);
        $entropy = 0.0;
        foreach ($freq as $count) {
            $p = $count / $len;
            $entropy -= $p * log($p, 2);
        }

        return $entropy > 4.2 ? 12.0 : 0.0;
    }

    private function fingerprintRisk(
        ?string $clientAcceptLanguage,
        ?string $clientTimezone,
        ?string $clientScreen,
    ): float {
        $lang = trim((string) ($clientAcceptLanguage ?? ''));
        $tz = trim((string) ($clientTimezone ?? ''));
        $screen = trim((string) ($clientScreen ?? ''));

        $risk = 0.0;
        if ($lang === '' && $tz === '') {
            $risk += 8.0;
        }
        if ($screen === '') {
            $risk += 4.0;
        }

        return $risk;
    }

    private function verdictFromRisk(float $risk, string $captchaBand, string $remoteIp): RegisterAntiBotVerdict
    {
        if ($captchaBand === 'block' || $risk >= 80.0) {
            RegisterSecurityLog::event('register_verdict_block', [
                'ip' => $remoteIp,
                'mode' => 'captcha',
                'risk' => $risk,
                'captcha_band' => $captchaBand,
            ]);

            return RegisterAntiBotVerdict::Block;
        }

        if ($risk >= 40.0) {
            RegisterSecurityLog::event('register_verdict_challenge', [
                'ip' => $remoteIp,
                'mode' => 'captcha',
                'risk' => $risk,
                'captcha_band' => $captchaBand,
            ]);

            return RegisterAntiBotVerdict::Challenge;
        }

        return RegisterAntiBotVerdict::Pass;
    }

    private function classifyScore(float $score): string
    {
        $blockBelow = (float) config('services.recaptcha.score_block_below', 0.3);
        $challengeMax = (float) config('services.recaptcha.score_challenge_max', 0.6);

        if ($score < $blockBelow) {
            return 'block';
        }

        if ($score <= $challengeMax) {
            return 'challenge';
        }

        return 'pass';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function verifyWithGoogle(string $secret, string $token, string $remoteIp): ?array
    {
        $timeoutMs = (int) config('services.recaptcha.timeout_ms', 5000);
        $seconds = max(0.5, $timeoutMs / 1000.0);

        try {
            $response = Http::timeout($seconds)
                ->asForm()
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);

            if (! $response->successful()) {
                return null;
            }

            /** @var array<string, mixed>|null $json */
            $json = $response->json();

            return is_array($json) ? $json : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
