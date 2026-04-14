<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SafeRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        return view('auth.register', [
            'redirect' => $request->string('redirect')->toString(),
        ]);
    }

    public function register(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:utenti,email'],
            'password' => ['required', 'string', 'min:8'],
            'redirect' => ['nullable', 'string'],
        ]);

        $user = User::query()->create([
            'nome' => $validated['nome'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

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
