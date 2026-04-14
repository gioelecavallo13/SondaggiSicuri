<header class="sm-page-header site-page-header">
    <div class="sm-page-header__title-wrap">
        @if(!empty($pageHeaderBackHref))
            <a href="{{ $pageHeaderBackHref }}" class="sm-back-link">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                {{ $pageHeaderBackLabel ?? 'Indietro' }}
            </a>
        @endif
        @if(!empty($pageHeaderSubtitle))
            <p class="section-label mb-1">{{ $pageHeaderSubtitle }}</p>
        @endif
        <h1 class="page-title site-page-header__title">{{ $pageHeaderTitle }}</h1>
    </div>
    @if(!empty($pageHeaderActions))
        <div class="sm-page-header__actions">
            {!! $pageHeaderActions !!}
        </div>
    @endif
</header>
