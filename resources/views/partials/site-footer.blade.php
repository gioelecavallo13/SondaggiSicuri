<footer class="site-footer site-footer--ds mt-auto">
    <div class="container site-shell">
        <div class="site-footer__inner d-flex flex-column flex-md-row flex-wrap justify-content-between align-items-start align-items-md-center gap-3 py-4 py-md-5">
            <p class="site-footer__copy mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'SondaggiModerni') }}</p>
            <nav class="site-footer__nav" aria-label="Footer">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item me-3"><a href="{{ route('about') }}">Chi siamo</a></li>
                    <li class="list-inline-item me-3"><a href="{{ route('contacts.index') }}">Contatti</a></li>
                    <li class="list-inline-item"><a href="{{ route('home') }}">Home</a></li>
                </ul>
            </nav>
        </div>
    </div>
</footer>
