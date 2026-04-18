<?php

namespace App\Http\Controllers;

use App\Enums\RegisterAntiBotVerdict;
use App\Models\User;
use App\Services\RegisterAntiBotService;
use App\Support\RegisterSecurityLog;
use App\Support\SafeRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View
    {
        return view('auth.login', [
            'redirect' => $request->string('redirect')->toString(),
        ]);
    }

    public function login(Request $request): RedirectResponse|View
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'redirect' => ['nullable', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()
                ->withErrors(['credentials' => 'Credenziali non valide.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->intended(SafeRedirect::afterLogin($credentials['redirect'] ?? null));
    }

    public function showRegister(Request $request): View
    {
        $minSubmitSeconds = random_int(15, 30) / 10.0;
        $request->session()->put('register_min_submit_seconds', $minSubmitSeconds);

        return view('auth.register', [
            'redirect' => $request->string('redirect')->toString(),
            'recaptcha_site_key' => (string) config('services.recaptcha.site_key', ''),
        ]);
    }

    public function register(Request $request): RedirectResponse|View
    {
        $captchaEnabled = trim((string) config('services.recaptcha.site_key', '')) !== ''
            && trim((string) config('services.recaptcha.secret_key', '')) !== '';

        $validated = $request->validate(
            [
                'nome' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:190', 'unique:utenti,email'],
                'password' => ['required', 'string', 'min:8'],
                'redirect' => ['nullable', 'string'],
                'website' => ['nullable', 'string', 'max:500'],
                'form_rendered_at' => ['required', 'integer', 'min:1'],
                'recaptcha_token' => [
                    Rule::requiredIf($captchaEnabled),
                    'nullable',
                    'string',
                    'max:4096',
                ],
                'client_accept_language' => ['nullable', 'string', 'max:500'],
                'client_timezone' => ['nullable', 'string', 'max:120'],
                'client_screen' => ['nullable', 'string', 'max:120'],
            ],
            [
                'form_rendered_at.required' => 'Impossibile completare la richiesta. Aggiorna la pagina e riprova.',
                'recaptcha_token.required' => 'Verifica di sicurezza richiesta. Aggiorna la pagina e riprova.',
            ]
        );

        if (trim((string) ($validated['website'] ?? '')) !== '') {
            RegisterSecurityLog::event('register_honeypot_triggered', [
                'ip' => (string) $request->ip(),
            ]);

            return back()
                ->withErrors(['register' => 'Non è stato possibile completare la registrazione. Riprova più tardi.'])
                ->withInput($request->only('nome', 'email', 'redirect'));
        }

        if (! $request->session()->has('register_min_submit_seconds')) {
            RegisterSecurityLog::event('register_session_missing_min_delay', [
                'ip' => (string) $request->ip(),
            ]);

            return back()
                ->withErrors(['register' => 'Sessione di registrazione scaduta. Ricarica la pagina e riprova.'])
                ->withInput($request->only('nome', 'email', 'redirect'));
        }

        $minSubmitSeconds = (float) $request->session()->get('register_min_submit_seconds');
        if ($minSubmitSeconds < 1.49 || $minSubmitSeconds > 3.01) {
            $minSubmitSeconds = 2.0;
        }

        $verdict = app(RegisterAntiBotService::class)->evaluate(
            (int) $validated['form_rendered_at'],
            $validated['recaptcha_token'] ?? null,
            (string) $request->ip(),
            $validated['client_accept_language'] ?? null,
            $validated['client_timezone'] ?? null,
            $validated['client_screen'] ?? null,
            strtolower(trim($validated['email'])),
            $minSubmitSeconds,
        );

        if ($verdict === RegisterAntiBotVerdict::Challenge) {
            return back()
                ->withErrors(['register' => 'Verifica non riuscita. Aggiorna la pagina e riprova.'])
                ->withInput($request->only('nome', 'email', 'redirect'));
        }

        if ($verdict === RegisterAntiBotVerdict::Block) {
            return back()
                ->withErrors(['register' => 'Non è stato possibile completare la registrazione. Riprova più tardi.'])
                ->withInput($request->only('nome', 'email', 'redirect'));
        }

        $user = User::query()->create([
            'nome' => $validated['nome'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        $request->session()->forget('register_min_submit_seconds');
        $request->session()->regenerate();

        return redirect()->intended(SafeRedirect::afterLogin($validated['redirect'] ?? null));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
