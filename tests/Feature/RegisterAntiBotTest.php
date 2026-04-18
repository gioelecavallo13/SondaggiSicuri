<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\RegisterSecurityLog;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegisterAntiBotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        // Evita che RECAPTCHA_* dal .env locale attivino Rule::requiredIf prima delle asserzioni sui test.
        Config::set('services.recaptcha.site_key', '');
        Config::set('services.recaptcha.secret_key', '');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registerFormPayload(array $overrides = []): array
    {
        return array_merge([
            'nome' => 'Utente Test',
            'email' => 'reg-'.uniqid('', true).'@example.test',
            'password' => 'password123xx',
            'website' => '',
            'form_rendered_at' => time() - 120,
            'recaptcha_token' => '',
            'client_accept_language' => 'it-IT,it;q=0.9',
            'client_timezone' => 'Europe/Rome',
            'client_screen' => '1920x1080@2',
        ], $overrides);
    }

    public function test_register_fails_without_prior_get(): void
    {
        Event::fake([MessageLogged::class]);
        $this->post('/register', $this->registerFormPayload())->assertSessionHasErrors('register');
        Event::assertDispatched(MessageLogged::class, function (MessageLogged $e): bool {
            return $e->level === 'warning'
                && $e->message === RegisterSecurityLog::LOG_KEY
                && ($e->context['event'] ?? null) === 'register_session_missing_min_delay';
        });
    }

    public function test_register_rejects_honeypot_and_logs(): void
    {
        Event::fake([MessageLogged::class]);
        $this->get(route('register'));
        $payload = $this->registerFormPayload(['website' => 'https://spam.example']);
        $this->post('/register', $payload)->assertSessionHasErrors('register');
        Event::assertDispatched(MessageLogged::class, function (MessageLogged $e): bool {
            return $e->level === 'warning'
                && $e->message === RegisterSecurityLog::LOG_KEY
                && ($e->context['event'] ?? null) === 'register_honeypot_triggered';
        });
    }

    public function test_register_rejects_disposable_domain_and_logs(): void
    {
        Event::fake([MessageLogged::class]);
        Config::set('registration.disposable_email_domains', ['yopmail.com']);
        $this->get(route('register'));
        $payload = $this->registerFormPayload(['email' => 'bot-'.uniqid().'@yopmail.com']);
        $this->post('/register', $payload)->assertSessionHasErrors('register');
        Event::assertDispatched(MessageLogged::class, function (MessageLogged $e): bool {
            return $e->level === 'warning'
                && $e->message === RegisterSecurityLog::LOG_KEY
                && ($e->context['event'] ?? null) === 'register_disposable_domain_blocked';
        });
    }

    public function test_register_guest_success_without_recaptcha_keys(): void
    {
        Config::set('services.recaptcha.site_key', '');
        Config::set('services.recaptcha.secret_key', '');
        $this->get(route('register'));
        $email = 'ok-'.uniqid().'@example.test';
        $payload = $this->registerFormPayload(['email' => $email]);
        $this->post('/register', $payload)->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('utenti', ['email' => $email]);
    }

    public function test_register_fast_submit_emits_timing_security_event(): void
    {
        Config::set('services.recaptcha.site_key', '');
        Config::set('services.recaptcha.secret_key', '');
        Event::fake([MessageLogged::class]);
        $this->get(route('register'));
        $payload = $this->registerFormPayload([
            'form_rendered_at' => time(),
        ]);
        $this->post('/register', $payload)->assertRedirect('/dashboard');
        Event::assertDispatched(MessageLogged::class, function (MessageLogged $e): bool {
            return $e->level === 'warning'
                && $e->message === RegisterSecurityLog::LOG_KEY
                && ($e->context['event'] ?? null) === 'register_timing_under_threshold';
        });
    }

    public function test_register_page_embeds_recaptcha_script_without_relying_on_vite(): void
    {
        Config::set('services.recaptcha.site_key', 'test-site-key');
        Config::set('services.recaptcha.secret_key', 'test-secret');
        $response = $this->get(route('register'));
        $response->assertOk();
        $response->assertSee('data-recaptcha-site-key="test-site-key"', false);
        $response->assertSee('www.google.com/recaptcha/api.js?render=', false);
        $response->assertSee('register-form', false);
    }

    public function test_register_with_recaptcha_calls_siteverify(): void
    {
        Config::set('services.recaptcha.site_key', 'test-site');
        Config::set('services.recaptcha.secret_key', 'test-secret');
        Http::fake([
            'https://www.google.com/recaptcha/api/*' => Http::response([
                'success' => true,
                'score' => 0.95,
                'action' => 'register',
            ], 200),
        ]);
        $this->get(route('register'));
        $email = 'captcha-'.uniqid().'@example.test';
        $payload = $this->registerFormPayload([
            'email' => $email,
            'recaptcha_token' => 'test-token',
        ]);
        $this->post('/register', $payload)->assertRedirect('/dashboard');
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'google.com/recaptcha/api/siteverify');
        });
    }

    public function test_register_recaptcha_emits_success_diagnostic_log_when_enabled(): void
    {
        Event::fake([MessageLogged::class]);
        Config::set('services.recaptcha.site_key', 'test-site');
        Config::set('services.recaptcha.secret_key', 'test-secret');
        Config::set('services.recaptcha.log_siteverify_success', true);
        Http::fake([
            'https://www.google.com/recaptcha/api/*' => Http::response([
                'success' => true,
                'score' => 0.91,
                'action' => 'register',
            ], 200),
        ]);
        $this->get(route('register'));
        $email = 'captcha-log-'.uniqid().'@example.test';
        $payload = $this->registerFormPayload([
            'email' => $email,
            'recaptcha_token' => 'test-token',
        ]);
        $this->post('/register', $payload)->assertRedirect('/dashboard');
        Event::assertDispatched(MessageLogged::class, function (MessageLogged $e): bool {
            return $e->level === 'info'
                && $e->message === 'register_recaptcha_siteverify_success'
                && ($e->context['action'] ?? null) === 'register'
                && ($e->context['band'] ?? null) === 'pass'
                && abs((float) ($e->context['score'] ?? 0) - 0.91) < 0.0001;
        });
    }
}
