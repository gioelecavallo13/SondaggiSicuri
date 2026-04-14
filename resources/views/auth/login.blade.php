@extends('layouts.auth')

@section('title', 'Login')

@section('content')
@php
    use App\Support\SafeRedirect;
    $redirectPath = $redirect !== '' ? explode('?', $redirect, 2)[0] : '';
    $redirectPathNormalized = $redirectPath !== '' ? (preg_replace('#/+$#', '', $redirectPath) ?: '/') : '';
    $needsSurveyLoginHint = session()->has('url.intended')
        || ($redirectPathNormalized !== '' && SafeRedirect::isAllowedSurveyTakeRelativePath($redirectPathNormalized));
    $credentialsError = $errors->has('credentials');
@endphp
<div class="site-auth-page">
    <div class="text-center mb-4 mb-md-5">
        <h1 class="site-auth-brand-title mb-2">{{ config('app.name', 'SondaggiModerni') }}</h1>
        <p class="site-auth-lead mb-0">Bentornato. Accedi per gestire i tuoi sondaggi.</p>
    </div>

    <div class="site-auth-card p-4 p-md-5">
        @if($needsSurveyLoginHint)
            <p class="small text-muted mb-3" role="status">Per compilare un sondaggio serve un account: accedi oppure registrati.</p>
        @endif

        @if($credentialsError)
            <div class="alert alert-danger py-2 px-3 mb-4" role="alert" id="login-credentials-alert">
                {{ $errors->first('credentials') }}
            </div>
        @endif

        <form
            method="post"
            action="{{ route('login') }}"
            id="login-form"
            data-sm-form-loading
            @if($credentialsError) aria-describedby="login-credentials-alert" @endif
        >
            @csrf
            @if($redirect !== '')
                <input type="hidden" name="redirect" value="{{ $redirect }}">
            @endif

            <div class="mb-4">
                <label class="site-auth-label" for="login-email">Indirizzo email</label>
                <input
                    class="form-control site-input @error('email') is-invalid @enderror"
                    id="login-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    inputmode="email"
                    placeholder="nome@esempio.it"
                    @if($credentialsError) aria-invalid="true" @endif
                    @error('email') aria-describedby="login-email-error" aria-invalid="true" @enderror
                >
                @error('email')
                    <div class="invalid-feedback d-block" id="login-email-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-2">
                <label class="site-auth-label" for="login-password">Password</label>
                <input
                    class="form-control site-input @error('password') is-invalid @enderror"
                    id="login-password"
                    type="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    @if($credentialsError) aria-invalid="true" @endif
                    @error('password') aria-describedby="login-password-error" aria-invalid="true" @enderror
                >
                @error('password')
                    <div class="invalid-feedback d-block" id="login-password-error">{{ $message }}</div>
                @enderror
            </div>

            <button class="site-btn-pill-primary site-btn-pill-primary--lg w-100 mt-2" type="submit">Accedi</button>
        </form>

        <p class="site-auth-cross mb-0 mt-4 pt-2">
            Non hai un account?
            <a href="{{ route('register', $redirect !== '' ? ['redirect' => $redirect] : []) }}">Registrati</a>
        </p>
    </div>
</div>
@endsection
