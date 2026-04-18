<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Accesso') — {{ config('app.name', 'Sondaggi') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex flex-column min-vh-100 site-auth-body">
<header class="site-auth-header site-glass-navbar border-0">
    <div class="container site-shell">
        <div class="d-flex align-items-center justify-content-between gap-2 py-3">
            <div class="flex-fill text-start">
                <a class="site-auth-back" href="{{ route('home') }}">
                    <i class="bi bi-arrow-left-short fs-4 align-middle" aria-hidden="true"></i>
                    <span class="align-middle">Home</span>
                </a>
            </div>
            <div class="flex-shrink-0 text-center px-2">
                <a class="site-brand site-brand--auth mb-0 text-decoration-none d-inline-block" href="{{ route('home') }}">{{ config('app.name', 'SondaggiModerni') }}</a>
            </div>
            <div class="flex-fill text-end" aria-hidden="true"></div>
        </div>
    </div>
</header>

<main class="flex-grow-1 d-flex flex-column site-auth-main">
    <div class="site-auth-blob site-auth-blob--tr" aria-hidden="true"></div>
    <div class="site-auth-blob site-auth-blob--bl" aria-hidden="true"></div>
    <div class="container site-shell py-4 flex-grow-1 d-flex flex-column justify-content-center">
        @yield('content')
    </div>
</main>

<footer class="site-auth-footer mt-auto py-4 px-3">
    <p class="mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'SondaggiModerni') }}</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
