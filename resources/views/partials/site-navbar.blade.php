@php
    $isDashboard = str_starts_with(request()->path(), 'dashboard');
    $isProfile = request()->routeIs('profile.show');
@endphp
<nav class="navbar navbar-expand-lg navbar-light site-navbar site-glass-navbar sticky-top py-0" id="site-navbar" aria-label="Navigazione principale">
    <div class="container site-shell">
        <a class="navbar-brand site-brand py-3" href="{{ route('home') }}">{{ config('app.name', 'SondaggiModerni') }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav"
                aria-controls="topNav" aria-expanded="false" aria-label="Apri menu di navigazione">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="topNav">
            <ul class="navbar-nav me-lg-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link px-lg-2{{ request()->routeIs('home') ? ' active' : '' }}" href="{{ route('home') }}"@if(request()->routeIs('home')) aria-current="page"@endif>Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-2{{ request()->routeIs('surveys.public.index') ? ' active' : '' }}" href="{{ route('surveys.public.index') }}"@if(request()->routeIs('surveys.public.index')) aria-current="page"@endif>Sondaggi pubblici</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-2{{ request()->routeIs('about') ? ' active' : '' }}" href="{{ route('about') }}"@if(request()->routeIs('about')) aria-current="page"@endif>Chi siamo</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-2{{ request()->routeIs('contacts.index', 'contacts.submit') ? ' active' : '' }}" href="{{ route('contacts.index') }}"@if(request()->routeIs('contacts.index', 'contacts.submit')) aria-current="page"@endif>Contatti</a>
                </li>
                @auth
                    <li class="nav-item">
                        <a class="nav-link px-lg-2{{ $isDashboard ? ' active' : '' }}" href="{{ route('dashboard') }}"@if($isDashboard) aria-current="page"@endif>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-lg-2{{ $isProfile ? ' active' : '' }}" href="{{ route('profile.show') }}"@if($isProfile) aria-current="page"@endif>Profilo</a>
                    </li>
                @endauth
            </ul>
            <div class="site-navbar-actions d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 ms-lg-3 pb-3 pb-lg-0">
                @auth
                    <form method="post" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button class="btn site-btn-nav-ghost" type="submit">Logout</button>
                    </form>
                @else
                    <a class="btn site-btn-nav-ghost" href="{{ route('login') }}">Login</a>
                    <a class="site-btn-pill-primary text-center" href="{{ route('register') }}">Registrati</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
