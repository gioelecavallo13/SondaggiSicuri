@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $surveyCountKpi = (int) ($dashboardStats['survey_count'] ?? 0);
    $totalParticipationsKpi = (int) ($dashboardStats['total_participations'] ?? 0);
    $userName = auth()->user()->nome ?? 'utente';
@endphp
<div class="page-app">
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-4 mb-4 mb-lg-5">
        <div>
            <h1 class="site-dash-greeting mb-2">Bentornato, {{ $userName }}</h1>
            <p class="text-muted site-body-lg mb-0">Ecco una panoramica della tua attività e dei tuoi sondaggi.</p>
        </div>
        <a class="site-btn-pill-primary site-btn-pill-primary--lg d-inline-flex align-items-center justify-content-center gap-2" href="{{ route('surveys.create') }}">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Nuovo sondaggio
        </a>
    </div>

    <div class="row g-4 mb-4 mb-lg-5">
        <div class="col-12 col-lg-6">
            <div class="site-dash-kpi site-dash-kpi--primary h-100" role="status">
                <div class="position-relative z-1">
                    <p class="site-dash-kpi__eyebrow mb-0">Sondaggi creati</p>
                    <div
                        class="site-dash-kpi__value"
                        data-count-up
                        data-count-target="{{ $surveyCountKpi }}"
                    >0</div>
                    <p class="site-dash-kpi__hint mb-0 d-flex align-items-center gap-1">
                        <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                        <span>Questionari attivi nel tuo account</span>
                    </p>
                </div>
                <i class="bi bi-clipboard-data site-dash-kpi__deco d-none d-sm-block" aria-hidden="true"></i>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="site-dash-kpi site-dash-kpi--secondary h-100" role="status">
                <p class="site-dash-kpi__eyebrow mb-0">Compilazioni totali</p>
                <div
                    class="site-dash-kpi__value"
                    data-count-up
                    data-count-target="{{ $totalParticipationsKpi }}"
                >0</div>
                <div class="site-dash-kpi__bar" aria-hidden="true"></div>
            </div>
        </div>
    </div>

    <section aria-labelledby="dash-surveys-heading">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <h2 id="dash-surveys-heading" class="site-dash-section-title mb-0">I tuoi sondaggi</h2>
        </div>

        @if($surveys->isEmpty())
            @php
                $emptyIconBootstrapClasses = 'bi bi-clipboard-data';
                $emptyTitle = 'Nessun sondaggio ancora';
                $emptyText = 'Crea il primo sondaggio per raccogliere risposte e vedere le statistiche.';
                $emptyCtaHref = route('surveys.create');
                $emptyCtaLabel = 'Nuovo sondaggio';
            @endphp
            @include('partials.empty-state')
            <p class="text-center text-muted small mt-3 mb-0">
                <a href="{{ route('home') }}">Torna alla home</a> per scoprire come funziona.
            </p>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach($surveys as $survey)
                    <article class="site-dash-survey-row{{ $survey->isScaduto() ? ' site-dash-survey-row--expired' : '' }}">
                        <div class="site-dash-survey-main flex-grow-1 min-w-0">
                            <div class="site-dash-survey-thumb" aria-hidden="true">
                                <i class="bi bi-bar-chart-line"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="site-dash-survey-title">{{ $survey->titolo }}</h3>
                                <div class="site-dash-survey-meta">
                                    @if($survey->is_pubblico)
                                        <span class="sm-badge-status sm-badge-status--public">Pubblico</span>
                                    @else
                                        <span class="sm-badge-status sm-badge-status--private">Privato</span>
                                    @endif
                                    @if($survey->isScaduto())
                                        <span class="sm-badge-status sm-badge-status--expired">Scaduto</span>
                                    @endif
                                    <span class="text-muted small d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                                        Creato {{ $survey->data_creazione?->timezone(config('app.timezone'))->format('d/m/Y') ?? '—' }}
                                    </span>
                                    @if($survey->isScaduto() && $survey->data_scadenza)
                                        <span class="text-muted small d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                            Scadenza: {{ $survey->data_scadenza->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                        </span>
                                    @endif
                                </div>
                                @if(filled($survey->descrizione))
                                    <p class="site-dash-survey-desc">{{ $survey->descrizione }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="site-dash-actions flex-shrink-0">
                            @unless($survey->isScaduto())
                                <a class="site-dash-action" href="{{ route('surveys.edit', $survey) }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i> Modifica
                                </a>
                            @endunless
                            <a class="site-dash-action site-dash-action--stats" href="{{ route('surveys.stats', $survey) }}">
                                <i class="bi bi-graph-up-arrow" aria-hidden="true"></i> Statistiche
                            </a>
                            <a class="site-dash-action" href="{{ route('surveys.show', $survey->takeRouteParameters()) }}">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                @if($survey->isScaduto())
                                    Vedi pagina
                                @else
                                    Apri
                                @endif
                            </a>
                            <button
                                type="button"
                                class="site-dash-icon-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteSurveyModal"
                                data-delete-url="{{ route('surveys.destroy', $survey) }}"
                                data-survey-title="{{ e($survey->titolo) }}"
                                aria-label="Elimina sondaggio {{ e($survey->titolo) }}"
                            >
                                <i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>

<div class="modal fade" id="deleteSurveyModal" tabindex="-1" aria-labelledby="deleteSurveyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h5 site-font-headline" id="deleteSurveyModalLabel">Elimina sondaggio</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0 text-muted" id="deleteSurveyModalText">Confermi l'eliminazione di questo sondaggio? L'azione non può essere annullata.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Annulla</button>
                <form id="deleteSurveyForm" method="post" action="" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger rounded-pill">Elimina</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
