@extends('layouts.app')

@section('title', 'Sondaggi pubblici')

@section('content')
<div
    class="page-app"
    id="sm-public-surveys-root"
    data-search-url="{{ route('surveys.public.search') }}"
>
    <header class="mb-4 mb-lg-5">
        <p class="section-eyebrow mb-2">Community</p>
        <h1 class="site-public-hero-title mb-3">Sondaggi pubblici</h1>
        <p class="text-muted site-body-lg mb-0 site-readable-width">
            Esplora i sondaggi aperti: cerca per titolo o descrizione e filtra per tag. Partecipa con un clic.
        </p>
    </header>

    <div id="sm-public-surveys-fetch-error" class="alert alert-warning d-none mb-3" role="status" aria-live="polite"></div>

    <div class="mb-4 sm-public-surveys-filter-card site-public-filter-panel">
        <div class="sm-public-surveys-filter-layout">
            <div class="sm-public-surveys-search-col">
                <label class="form-label mb-2 sm-public-surveys-search-heading" for="sm-public-surveys-q">
                    <i class="bi bi-search me-2 site-icon-muted" aria-hidden="true"></i>Ricerca sondaggi
                </label>
                <div class="input-group sm-public-surveys-search-group">
                    <span class="input-group-text sm-public-surveys-search-addon" aria-hidden="true">
                        <i class="bi bi-search"></i>
                    </span>
                    <input
                        type="search"
                        class="form-control sm-public-surveys-search-input"
                        id="sm-public-surveys-q"
                        name="q"
                        autocomplete="off"
                        placeholder="Cerca per titolo o descrizione…"
                        value="{{ $searchQuery }}"
                    >
                </div>
            </div>
            <div class="sm-public-surveys-tags-col">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <span class="form-label mb-0 sm-public-surveys-tags-heading">
                        <i class="bi bi-tags me-2 site-icon-muted" aria-hidden="true"></i>Filtra per categoria
                    </span>
                    <button
                        type="button"
                        class="btn btn-sm btn-link text-decoration-none p-0 sm-public-surveys-reset"
                        id="sm-public-surveys-reset"
                        disabled
                        aria-label="Reimposta ricerca e filtri tag"
                    >
                        Reset filtri
                    </button>
                </div>
                <div
                    class="sm-public-surveys-tags-scroll d-flex flex-wrap gap-2"
                    role="group"
                    aria-label="Filtri per tag"
                >
                    @foreach($allTags as $tag)
                        <label
                            class="sm-tag-filter-label sm-tag-chip mb-0 {{ in_array((int) $tag->id, $selectedTagIds, true) ? 'active' : '' }}"
                            data-tag-name="{{ $tag->nome }}"
                        >
                            <input
                                class="d-none sm-tag-filter-input"
                                type="checkbox"
                                name="tags[]"
                                value="{{ $tag->id }}"
                                {{ in_array((int) $tag->id, $selectedTagIds, true) ? 'checked' : '' }}
                            >
                            <span class="sm-tag-chip__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                            <span class="sm-tag-chip__text">{{ $tag->nome }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div
        id="sm-public-surveys-active-filters"
        class="sm-public-surveys-active-filters d-none mb-4"
        role="region"
        aria-label="Filtri attivi sull'elenco"
    >
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="sm-public-surveys-active-filters__label">Filtri attivi</span>
            <div id="sm-public-surveys-active-filters-chips" class="d-flex flex-wrap align-items-center gap-2"></div>
        </div>
    </div>

    <div class="position-relative sm-public-surveys-results-wrap" id="sm-public-surveys-results-wrap">
        <div id="sm-public-surveys-cards">
            @include('surveys.partials.public-survey-cards', [
                'surveys' => $surveys->getCollection(),
                'gridClass' => 'col-md-6 col-lg-4',
                'rowGutterClass' => 'g-4',
            ])
        </div>
        <div id="sm-public-surveys-pagination">
            @include('surveys.partials.public-survey-pagination', ['paginator' => $surveys])
        </div>
    </div>
</div>
@endsection
