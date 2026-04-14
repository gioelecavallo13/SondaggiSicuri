@extends('layouts.app')

@section('title', 'Statistiche')

@section('content')
@php
    $total = (int) $stats['total_responses'];
    $shareHref = route('surveys.show', ['sondaggio' => $survey['access_token']]);
    $statsDataUrl = route('surveys.stats.data', $survey['id']);
    $reportHref = route('surveys.stats.report', $survey['id']);
@endphp
<div
    class="page-app"
    data-sm-stats-poll
    data-stats-data-url="{{ $statsDataUrl }}"
    data-stats-refresh-interval-seconds="{{ (int) $stats_refresh_interval_seconds }}"
    data-stats-initial-total="{{ $total }}"
>
    <header class="mb-4 mb-lg-5">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-4">
            <div>
                <span class="site-stats-pill {{ $is_scaduto ? 'site-stats-pill--closed' : '' }}">{{ $is_scaduto ? 'Sondaggio chiuso' : 'Report attivo' }}</span>
                <h1 class="site-stats-hero-title mb-3">{{ $survey['titolo'] }}</h1>
                <p class="site-stats-hero-lead mb-0">
                    @if($is_scaduto)
                        Riepilogo delle compilazioni e distribuzione delle risposte per ogni domanda. Il sondaggio non accetta più risposte: la pagina pubblica è in sola lettura per chi aveva il link.
                    @else
                        Riepilogo delle compilazioni e distribuzione delle risposte per ogni domanda. Condividi il link del sondaggio per raccogliere più dati.
                    @endif
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                <a class="btn site-card border-0 d-inline-flex align-items-center gap-2 px-4 py-3 rounded-4 fw-semibold text-body shadow-sm" href="{{ $reportHref }}">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    Stampa report
                </a>
                <a class="btn site-card border-0 d-inline-flex align-items-center gap-2 px-4 py-3 rounded-4 fw-semibold text-body shadow-sm" href="{{ $shareHref }}">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    {{ $is_scaduto ? 'Vedi pagina' : 'Apri sondaggio' }}
                </a>
                @unless($is_scaduto)
                    <button
                        type="button"
                        class="site-btn-pill-primary d-inline-flex align-items-center gap-2 px-4 py-3 border-0"
                        data-sm-stats-copy-link
                        data-share-url="{{ $shareHref }}"
                        aria-label="Copia negli appunti il link della pagina del sondaggio"
                    >
                        <i class="bi bi-share" aria-hidden="true"></i>
                        Condividi link
                    </button>
                @endunless
            </div>
        </div>
    </header>

    <div id="sm-stats-live-root">
        <div class="row g-4 mb-4 mb-lg-5">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="site-stats-kpi-tile" role="status">
                    <div class="site-stats-kpi-tile__icon">
                        <i class="bi bi-people-fill" aria-hidden="true"></i>
                    </div>
                    <div class="site-stats-kpi-tile__value" data-sm-stats-total>{{ $total }}</div>
                    <div class="site-stats-kpi-tile__label">Compilazioni totali</div>
                </div>
            </div>
        </div>

        @if($total === 0)
            @php
                $emptyIconBootstrapClasses = 'bi bi-graph-up-arrow';
                $emptyTitle = 'Ancora nessuna risposta';
                if ($is_scaduto) {
                    $emptyText = 'Non risultano compilazioni. Il sondaggio è chiuso: la pagina pubblica resta consultabile in sola lettura.';
                    $emptyCtaHref = $shareHref;
                    $emptyCtaLabel = 'Vedi pagina';
                } else {
                    $emptyText = 'Condividi il link del sondaggio: quando qualcuno compilerà, qui vedrai grafici e percentuali.';
                    $emptyCtaHref = $shareHref;
                    $emptyCtaLabel = 'Apri link pubblico';
                }
            @endphp
            @include('partials.empty-state')
        @else
            <h2 class="site-dash-section-title mb-4">Dettaglio domande</h2>
            <div class="row g-4">
                @foreach($stats['questions'] as $index => $question)
                    @php
                        $tipoLabel = ($question['tipo'] ?? '') === 'multipla' ? 'Scelta multipla' : 'Scelta singola';
                    @endphp
                    <div class="col-lg-6">
                        <article class="site-stats-q-card h-100 d-flex flex-column" data-question-id="{{ (int) $question['id'] }}">
                            <span class="site-stats-q-card__eyebrow">Domanda {{ $index + 1 }} · {{ $tipoLabel }}</span>
                            <h3 class="site-stats-q-card__title">{{ $question['testo'] }}</h3>
                            <div class="sm-chart-wrap mb-3" data-chart-id="chart-{{ (int) $question['id'] }}">
                                <div class="sm-chart-skeleton" aria-hidden="true"></div>
                                <canvas id="chart-{{ (int) $question['id'] }}" class="sm-chart-canvas" height="200" aria-label="Grafico distribuzione risposte"></canvas>
                            </div>
                            <div class="mt-auto">
                                @foreach($question['options'] as $option)
                                    @php $pct = (float) $option['percentuale']; @endphp
                                    <div class="site-stats-option-row" data-option-id="{{ (int) $option['id'] }}">
                                        <div class="d-flex justify-content-between small fw-semibold mb-1 gap-2">
                                            <span class="min-w-0">{{ $option['testo'] }}</span>
                                            <span class="text-muted text-nowrap flex-shrink-0" data-sm-stats-option-meta>
                                                {{ (int) $option['votes'] }} · {{ $pct }}%
                                            </span>
                                        </div>
                                        <div class="progress site-stats-progress" role="progressbar" aria-valuenow="{{ (int) round($pct) }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $option['testo'] }}: {{ $pct }}%">
                                            <div class="progress-bar" style="width: {{ min(100, max(0, $pct)) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @include('surveys.partials.stats-participants-section', ['participant_insights' => $participant_insights, 'stats' => $stats])
</div>
<script>
window.__initialSurveyStats = @json($stats);
</script>
@endsection
