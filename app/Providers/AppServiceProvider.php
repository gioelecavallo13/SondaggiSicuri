<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRegistrationRateLimiting();
    }

    private function configureRegistrationRateLimiting(): void
    {
        RateLimiter::for('register-ip', function (Request $request) {
            return Limit::perMinute(5)->by('register-ip:'.$request->ip());
        });

        RateLimiter::for('register-ip-email', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', '')));
            $key = 'register-ip-email:'.$request->ip().':'.hash('sha256', $email);

            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('register-fingerprint', function (Request $request) {
            return Limit::perMinute(8)->by('register-fp:'.$this->registerFingerprintKey($request));
        });

        RateLimiter::for('register-progressive', function (Request $request) {
            $ip = $request->ip();
            $email = strtolower(trim((string) $request->input('email', '')));
            $emailKey = hash('sha256', $email);
            $fp = $this->registerFingerprintKey($request);

            return [
                Limit::perMinutes(15, 25)->by('register-prog-ip:'.$ip),
                Limit::perMinutes(15, 40)->by('register-prog-em:'.$ip.':'.$emailKey),
                Limit::perMinutes(15, 30)->by('register-prog-fp:'.$fp),
            ];
        });
    }

    private function registerFingerprintKey(Request $request): string
    {
        $ip = (string) $request->ip();
        $ua = strtolower(substr(preg_replace('/\s+/', ' ', (string) $request->userAgent()), 0, 400));
        $lang = strtolower(substr((string) $request->input('client_accept_language', ''), 0, 300));
        $tz = strtolower(substr((string) $request->input('client_timezone', ''), 0, 120));

        return hash('sha256', $ip.'|'.$ua.'|'.$lang.'|'.$tz);
    }
}
