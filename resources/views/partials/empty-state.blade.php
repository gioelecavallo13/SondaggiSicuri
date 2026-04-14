<div class="sm-empty-state site-empty-state rounded-4" role="status">
    <div class="sm-empty-state__icon" aria-hidden="true">
        <i class="{{ $emptyIconBootstrapClasses }}"></i>
    </div>
    <h2 class="sm-empty-state__title h5">{{ $emptyTitle }}</h2>
    <p class="sm-empty-state__text mb-0">{{ $emptyText }}</p>
    @if(!empty($emptyCtaHref) && !empty($emptyCtaLabel))
        <a class="btn site-btn-pill-primary mt-3 d-inline-flex align-items-center justify-content-center" href="{{ $emptyCtaHref }}">{{ $emptyCtaLabel }}</a>
    @endif
</div>
