@extends('layouts.app')

@section('title', 'Profilo utente')

@section('content')
@php
    /** @var \App\Models\User $user */
    $registeredAt = $user->data_creazione;
    $name = trim((string) $user->nome);
    $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
    if (count($parts) >= 2) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
    } elseif ($name !== '') {
        $initials = mb_strtoupper(mb_substr($name, 0, min(2, mb_strlen($name))));
    } else {
        $initials = '?';
    }
    $firstName = $parts[0] ?? $name;
    $profilePhotoUrl = $user->profilePhotoUrl();
@endphp
<div class="page-app">
    <section class="site-profile-hero" aria-labelledby="profile-page-title">
        <div class="site-profile-hero__blobs" aria-hidden="true"></div>
        <div class="site-profile-hero__inner">
            <a href="{{ route('dashboard') }}" class="sm-back-link mb-3">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Dashboard
            </a>
            <div class="d-flex flex-column flex-md-row align-items-start gap-4">
                <div
                    class="site-profile-avatar-block d-flex flex-column align-items-start gap-2"
                    id="profile-avatar-root"
                    data-upload-url="{{ route('profile.photo.upload') }}"
                    data-max-bytes="2097152"
                >
                    <input
                        type="file"
                        name="photo"
                        id="profile-photo-input"
                        class="visually-hidden"
                        accept="image/jpeg,image/jpg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                        tabindex="-1"
                    >
                    <label
                        for="profile-photo-input"
                        id="profile-avatar-circle"
                        class="site-profile-avatar site-profile-avatar--interactive mb-0 {{ $profilePhotoUrl ? 'site-profile-avatar--has-photo' : '' }}"
                        aria-label="Carica o modifica la foto profilo"
                    >
                        @if($profilePhotoUrl)
                            <img
                                class="site-profile-avatar__img"
                                src="{{ $profilePhotoUrl }}"
                                alt=""
                                width="72"
                                height="72"
                                loading="lazy"
                                decoding="async"
                            >
                            <span class="site-profile-avatar__initials visually-hidden">{{ $initials }}</span>
                        @else
                            <span class="site-profile-avatar__initials">{{ $initials }}</span>
                        @endif
                        <span class="site-profile-avatar__hover" aria-hidden="true">
                            <i class="bi bi-camera-fill"></i>
                        </span>
                    </label>
                    <div class="alert alert-danger py-2 px-3 mb-0 d-none" id="profile-photo-alert" role="alert"></div>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <span class="site-pill-secondary mb-2 d-inline-block">Il tuo account</span>
                    <h1 id="profile-page-title" class="site-dash-greeting mb-2">
                        Ciao{{ $firstName !== '' ? ', '.$firstName : '' }}
                    </h1>
                    <p class="lead text-muted site-body-lg mb-0 site-readable-width">
                        Qui trovi i dati del profilo e le opzioni per gestire l’accesso in sicurezza.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 g-lg-5 align-items-stretch">
        <div class="col-12 col-lg-7">
            <section aria-labelledby="profile-personal-heading" class="h-100 d-flex flex-column">
                <h2 id="profile-personal-heading" class="site-headline-md mb-3">Dati personali</h2>
                <div class="site-profile-panel flex-grow-1">
                    <p class="site-profile-panel__intro mb-0 pb-3 border-bottom border-light-subtle">
                        Informazioni salvate nel tuo account e visibili solo a te.
                    </p>
                    <div class="site-profile-field">
                        <span class="site-icon-tile site-icon-tile--pf" aria-hidden="true"><i class="bi bi-person"></i></span>
                        <div class="site-profile-field__body">
                            <p class="site-profile-field__label">Nome</p>
                            <p class="site-profile-field__value">{{ $user->nome }}</p>
                        </div>
                    </div>
                    <div class="site-profile-field">
                        <span class="site-icon-tile site-icon-tile--pf" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                        <div class="site-profile-field__body">
                            <p class="site-profile-field__label">Email</p>
                            <p class="site-profile-field__value">
                                <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                            </p>
                        </div>
                    </div>
                    <div class="site-profile-field">
                        <span class="site-icon-tile site-icon-tile--pf" aria-hidden="true"><i class="bi bi-calendar-event"></i></span>
                        <div class="site-profile-field__body">
                            <p class="site-profile-field__label">Data di registrazione</p>
                            <p class="site-profile-field__value">
                                @if($registeredAt)
                                    <time datetime="{{ $registeredAt->toIso8601String() }}">{{ $registeredAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</time>
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="site-profile-field">
                        <span class="site-icon-tile site-icon-tile--pf" aria-hidden="true"><i class="bi bi-hash"></i></span>
                        <div class="site-profile-field__body">
                            <p class="site-profile-field__label">ID account</p>
                            <p class="site-profile-field__value font-monospace">{{ $user->id }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-5">
            <section aria-labelledby="profile-account-heading" class="h-100 d-flex flex-column">
                <h2 id="profile-account-heading" class="site-headline-md mb-3">Sicurezza e accesso</h2>
                <div class="site-profile-panel flex-grow-1 d-flex flex-column">
                    <div class="d-flex gap-3 mb-3">
                        <span class="site-icon-tile site-icon-tile--sec flex-shrink-0" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                        <div>
                            <p class="site-profile-panel__title mb-1">Sessione</p>
                            <p class="site-profile-panel__intro mb-0">
                                Disconnettiti quando usi un computer condiviso. Altre impostazioni account potranno essere aggiunte in seguito.
                            </p>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-3 mt-auto pt-2">
                        <form method="post" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button class="site-profile-logout-btn" type="submit">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                Esci dall’account
                            </button>
                        </form>
                        <a href="{{ route('dashboard') }}" class="site-profile-back-link">
                            <i class="bi bi-speedometer2" aria-hidden="true"></i>
                            Torna alla dashboard
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
