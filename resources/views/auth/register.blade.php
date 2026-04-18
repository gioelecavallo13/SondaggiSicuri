@extends('layouts.auth')

@section('title', 'Registrazione')

@section('content')
<div class="site-auth-page">
    <div class="text-center mb-4 mb-md-5">
        <h1 class="site-auth-brand-title mb-2">{{ config('app.name', 'SondaggiModerni') }}</h1>
        <p class="site-auth-lead mb-0">Crea un account gratuito per iniziare a costruire e condividere sondaggi.</p>
    </div>

    <div class="site-auth-card p-4 p-md-5">
        @if($errors->any())
            <div class="alert alert-danger py-2 px-3 mb-4" role="alert" aria-live="polite" id="register-summary-errors">
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="alert alert-warning py-2 px-3 mb-4 d-none" role="alert" aria-live="polite" id="register-client-error"></div>

        <form
            method="post"
            action="{{ route('register') }}"
            id="register-form"
            data-recaptcha-site-key="{{ e($recaptcha_site_key) }}"
            @if($errors->any()) aria-describedby="register-summary-errors" @endif
        >
            @csrf
            @if($redirect !== '')
                <input type="hidden" name="redirect" value="{{ $redirect }}">
            @endif

            {{-- Anti-bot: honeypot (lasciare vuoto); compilazione = sospetto lato server --}}
            <div class="position-absolute" style="left: -10000px; top: auto; width: 1px; height: 1px; overflow: hidden;" aria-hidden="true">
                <label for="reg-website">Sito web</label>
                <input type="text" name="website" id="reg-website" tabindex="-1" autocomplete="off" value="">
            </div>

            <input type="hidden" name="form_rendered_at" value="{{ (string) now()->getTimestamp() }}">
            <input type="hidden" name="recaptcha_token" value="">
            <input type="hidden" name="client_accept_language" value="">
            <input type="hidden" name="client_timezone" value="">
            <input type="hidden" name="client_screen" value="">

            <div class="mb-4">
                <label class="site-auth-label" for="reg-nome">Nome</label>
                <input
                    class="form-control site-input @error('nome') is-invalid @enderror"
                    id="reg-nome"
                    type="text"
                    name="nome"
                    value="{{ old('nome') }}"
                    required
                    autocomplete="name"
                    placeholder="Il tuo nome"
                    @error('nome') aria-describedby="reg-nome-error" aria-invalid="true" @enderror
                >
                @error('nome')
                    <div class="invalid-feedback d-block" id="reg-nome-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="site-auth-label" for="reg-email">Indirizzo email</label>
                <input
                    class="form-control site-input @error('email') is-invalid @enderror"
                    id="reg-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    inputmode="email"
                    placeholder="nome@esempio.it"
                    @error('email') aria-describedby="reg-email-error" aria-invalid="true" @enderror
                >
                @error('email')
                    <div class="invalid-feedback d-block" id="reg-email-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-2">
                <label class="site-auth-label" for="reg-password">Password</label>
                <input
                    class="form-control site-input @error('password') is-invalid @enderror"
                    id="reg-password"
                    type="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Almeno 8 caratteri"
                    @error('password') aria-describedby="reg-password-error" aria-invalid="true" @enderror
                >
                @error('password')
                    <div class="invalid-feedback d-block" id="reg-password-error">{{ $message }}</div>
                @enderror
            </div>

            <button class="site-btn-pill-primary site-btn-pill-primary--lg w-100 mt-2" type="submit">Crea account</button>
        </form>

        <p class="site-auth-cross mb-0 mt-4 pt-2">
            Hai già un account?
            <a href="{{ route('login', $redirect !== '' ? ['redirect' => $redirect] : []) }}">Accedi</a>
        </p>
    </div>
</div>
@if(trim((string) ($recaptcha_site_key ?? '')) !== '')
    @push('scripts')
        @include('partials.register-recaptcha-inline')
    @endpush
@endif
@endsection
