@php
    $staggerReveal = $staggerReveal ?? false;
    $gridClass = $gridClass ?? 'col-md-6';
    $rowGutterClass = $rowGutterClass ?? 'g-3';
@endphp
@if($surveys->isEmpty())
    <div class="sm-empty-state" id="sm-public-surveys-empty">
        <div class="sm-empty-state__icon" aria-hidden="true"><i class="bi bi-search"></i></div>
        <p class="sm-empty-state__title mb-2">Nessun sondaggio trovato</p>
        <p class="text-muted small mb-0">Prova a cambiare i filtri o la ricerca.</p>
    </div>
@else
    <div class="row {{ $rowGutterClass }}" id="sm-public-surveys-grid">
        @foreach($surveys as $i => $survey)
            @php
                $viewerAnswered = (bool) ($survey->viewer_has_responded ?? false);
            @endphp
            <article
                class="{{ $gridClass }}"
                @if($staggerReveal) data-reveal data-reveal-stagger style="--stagger-index: {{ min($i, 5) }}" @endif
                @if($viewerAnswered) aria-label="Sondaggio già compilato: {{ $survey->titolo }}" @endif
            >
                <div @class([
                    'site-public-card h-100 d-flex flex-column',
                    'site-public-card--answered' => $viewerAnswered,
                ])>
                    <div class="site-public-card__media flex-shrink-0">
                        <div class="site-public-card__gradient" aria-hidden="true"></div>
                        <div class="site-public-card__tags">
                            @if($survey->isScaduto())
                                <span class="site-public-card__tag site-public-card__tag--expired">Scaduto</span>
                            @endif
                            @forelse($survey->tags as $tag)
                                <span class="site-public-card__tag">{{ $tag->nome }}</span>
                            @empty
                                @if(! $survey->isScaduto())
                                    <span class="site-public-card__tag">Sondaggio</span>
                                @endif
                            @endforelse
                        </div>
                    </div>
                    <div class="d-flex flex-column flex-grow-1 p-4">
                        <h3 class="site-public-card__title">{{ $survey->titolo }}</h3>
                        <p class="survey-desc-preview flex-grow-1 small mb-3">{{ \Illuminate\Support\Str::limit($survey->descrizione ?? '', 220) }}</p>
                        <ul class="list-unstyled site-public-card__meta mb-3 mt-0 small">
                            <li class="mb-1">
                                <i class="bi bi-people me-1" aria-hidden="true"></i>{{ $survey->risposte_count }} {{ $survey->risposte_count === 1 ? 'risposta' : 'risposte' }}
                            </li>
                            <li class="mb-1">
                                <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                @if($survey->data_scadenza)
                                    Scadenza: {{ $survey->data_scadenza->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                @else
                                    Senza scadenza
                                @endif
                            </li>
                            <li>
                                <i class="bi bi-person me-1" aria-hidden="true"></i>{{ $survey->autore?->nome ?? '—' }}
                            </li>
                        </ul>
                        <div class="mt-auto">
                            @if($survey->isScaduto())
                                <span class="site-public-card__cta-muted w-100 d-inline-flex align-items-center justify-content-center rounded-4 py-3 px-3 fw-semibold small" role="status">Non più attivo</span>
                            @elseif($viewerAnswered)
                                <a
                                    class="site-public-card__cta-muted site-public-card__cta-reopen w-100 d-inline-flex align-items-center justify-content-center rounded-4 py-3 px-3 fw-semibold small gap-2 text-decoration-none"
                                    href="{{ route('surveys.show', $survey->takeRouteParameters()) }}"
                                    title="Hai già inviato le tue risposte"
                                >
                                    <i class="bi bi-check2-circle site-public-card__cta-reopen-icon flex-shrink-0" aria-hidden="true"></i>
                                    <span class="visually-hidden">Apri il sondaggio (già compilato)</span>
                                </a>
                            @else
                                <a class="site-btn-pill-primary w-100 d-inline-flex" href="{{ route('surveys.show', $survey->takeRouteParameters()) }}">Compila</a>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
