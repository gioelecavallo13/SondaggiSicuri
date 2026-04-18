<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Log strutturati per osservabilità anti-abuso sulla registrazione.
 * Chiave messaggio fissa {@see self::LOG_KEY} per filtri in log aggregator.
 *
 * Eventi `event` in contesto (non esaustivo): register_honeypot_triggered,
 * register_session_missing_min_delay, register_disposable_domain_blocked,
 * register_captcha_*, register_verdict_*, register_timing_under_threshold,
 * register_throttled (429 su POST /register).
 */
final class RegisterSecurityLog
{
    public const string LOG_KEY = 'register_security';

    /**
     * @param  array<string, mixed>  $context
     */
    public static function event(string $event, array $context = []): void
    {
        Log::warning(self::LOG_KEY, array_merge([
            'event' => $event,
        ], $context));
    }
}
