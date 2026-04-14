@php
    /** @var array $participant_insights */
    use Illuminate\Support\Carbon;

    $pi = $participant_insights;
    $showsList = (bool) ($pi['shows_participant_list'] ?? false);
    $showsAnswers = (bool) ($pi['shows_individual_answers'] ?? false);
    $count = (int) ($pi['participant_count'] ?? 0);
    $participants = $pi['participants'] ?? [];
@endphp
<section
    id="stats-participants-section"
    class="mt-5"
    aria-labelledby="stats-participants-heading"
    data-participant-mode="{{ $pi['mode'] ?? '' }}"
>
    <h2 id="stats-participants-heading" class="site-dash-section-title mb-3">Partecipanti</h2>

    @if (! $showsList)
        <div class="alert alert-info border-0 rounded-4 shadow-sm mb-0" role="region" aria-labelledby="stats-participants-anon-title">
            <div class="d-flex gap-3">
                <i class="bi bi-shield-lock flex-shrink-0 mt-1" aria-hidden="true"></i>
                <div>
                    <p id="stats-participants-anon-title" class="fw-semibold mb-1">Privacy del sondaggio</p>
                    <p class="mb-0 small">{{ $pi['anonymous_explanation'] ?? '' }}</p>
                </div>
            </div>
        </div>
    @elseif ($count === 0)
        <div class="site-card border rounded-4 p-4 shadow-sm">
            <p class="text-muted mb-0">
                @if ((int) ($stats['total_responses'] ?? 0) === 0)
                    Non risultano compilazioni: quando arriveranno risposte, l’elenco si popolerà per le modalità che collegano le risposte all’account.
                @else
                    Non risultano compilazioni associate a un account utente. Le statistiche aggregate sopra riflettono comunque tutte le risposte ricevute (inclusi invii anonimi, se previsti).
                @endif
            </p>
        </div>
    @else
        <div data-sm-participants-filter-root>
            <div class="mb-4">
                <label class="form-label fw-semibold small text-muted mb-2" for="sm-participants-search-input">Cerca per nome o email</label>
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-8 col-lg-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" id="sm-participants-search-addon"><i class="bi bi-search" aria-hidden="true"></i></span>
                            <input
                                type="search"
                                class="form-control border-start-0"
                                id="sm-participants-search-input"
                                data-sm-participants-search-input
                                autocomplete="off"
                                aria-describedby="sm-participants-search-status"
                                placeholder="Filtra l’elenco…"
                            >
                        </div>
                    </div>
                    <div class="col-12">
                        <p id="sm-participants-search-status" class="small text-muted mb-0 mt-1" aria-live="polite">
                            <span data-sm-participants-visible-count>{{ $count }}</span> su {{ $count }} {{ $count === 1 ? 'partecipante' : 'partecipanti' }} mostrati
                        </p>
                    </div>
                </div>
            </div>

        {{-- Desktop: tabella --}}
        <div class="d-none d-lg-block sm-participants-desktop">
            <div class="table-responsive site-card border rounded-4 shadow-sm">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-muted small text-uppercase">#</th>
                            <th scope="col" class="text-muted small text-uppercase">Nome</th>
                            <th scope="col" class="text-muted small text-uppercase">Email</th>
                            <th scope="col" class="text-muted small text-uppercase">Compilazione</th>
                            @if ($showsAnswers)
                                <th scope="col" class="text-muted small text-uppercase">Risposte</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $p)
                            @php
                                $u = $p['user'] ?? null;
                                $nome = $u['nome'] ?? null;
                                $email = $u['email'] ?? null;
                                $nomeDisp = $nome !== null && $nome !== '' ? $nome : '—';
                                $emailDisp = $email !== null && $email !== '' ? $email : '—';
                                $hay = mb_strtolower(trim(($nome ?? '').' '.($email ?? '')));
                                $compiledLabel = '—';
                                if (! empty($p['data_compilazione'])) {
                                    try {
                                        $compiledLabel = Carbon::parse($p['data_compilazione'])->timezone(config('app.timezone'))->format('d/m/Y H:i');
                                    } catch (\Throwable) {
                                        $compiledLabel = '—';
                                    }
                                }
                            @endphp
                            <tr data-sm-participant-row data-sm-participant-haystack="{{ e($hay) }}">
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td class="fw-medium">{{ $nomeDisp }}</td>
                                <td>
                                    @if ($email !== null && $email !== '')
                                        <a href="mailto:{{ e($email) }}" class="text-decoration-none">{{ $emailDisp }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-nowrap text-muted small">{{ $compiledLabel }}</td>
                                @if ($showsAnswers)
                                    <td class="small min-w-0">
                                        @if (! empty($p['answers']) && is_array($p['answers']))
                                            <ul class="mb-0 ps-3">
                                                @foreach ($p['answers'] as $ans)
                                                    <li class="mb-2">
                                                        <span class="fw-semibold d-block">{{ $ans['domanda_testo'] ?? '' }}</span>
                                                        @php $opts = $ans['opzioni'] ?? []; @endphp
                                                        @if (count($opts) === 1)
                                                            <span class="text-muted">{{ $opts[0]['testo'] ?? '' }}</span>
                                                        @else
                                                            <ul class="mb-0 ps-3 text-muted">
                                                                @foreach ($opts as $o)
                                                                    <li>{{ $o['testo'] ?? '' }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile: card --}}
        <div class="d-lg-none d-flex flex-column gap-3 sm-participants-mobile">
            @foreach ($participants as $p)
                @php
                    $u = $p['user'] ?? null;
                    $nome = $u['nome'] ?? null;
                    $email = $u['email'] ?? null;
                    $nomeDisp = $nome !== null && $nome !== '' ? $nome : '—';
                    $emailDisp = $email !== null && $email !== '' ? $email : '—';
                    $hay = mb_strtolower(trim(($nome ?? '').' '.($email ?? '')));
                    $compiledLabel = '—';
                    if (! empty($p['data_compilazione'])) {
                        try {
                            $compiledLabel = Carbon::parse($p['data_compilazione'])->timezone(config('app.timezone'))->format('d/m/Y H:i');
                        } catch (\Throwable) {
                            $compiledLabel = '—';
                        }
                    }
                @endphp
                <article
                    class="site-card border rounded-4 p-4 shadow-sm"
                    data-sm-participant-row
                    data-sm-participant-haystack="{{ e($hay) }}"
                >
                    <h3 class="h6 fw-semibold mb-3">Partecipante {{ $loop->iteration }}</h3>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-muted">Nome</dt>
                        <dd class="col-sm-8 mb-2">{{ $nomeDisp }}</dd>
                        <dt class="col-sm-4 text-muted">Email</dt>
                        <dd class="col-sm-8 mb-2">
                            @if ($email !== null && $email !== '')
                                <a href="mailto:{{ e($email) }}" class="text-decoration-none">{{ $emailDisp }}</a>
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="col-sm-4 text-muted">Compilazione</dt>
                        <dd class="col-sm-8 mb-0 text-muted">{{ $compiledLabel }}</dd>
                    </dl>
                    @if ($showsAnswers && ! empty($p['answers']) && is_array($p['answers']))
                        <div class="border-top mt-3 pt-3">
                            <p class="small fw-semibold text-muted text-uppercase mb-2">Risposte</p>
                            <ul class="mb-0 ps-3 small">
                                @foreach ($p['answers'] as $ans)
                                    <li class="mb-2">
                                        <span class="fw-semibold d-block">{{ $ans['domanda_testo'] ?? '' }}</span>
                                        @php $opts = $ans['opzioni'] ?? []; @endphp
                                        @if (count($opts) === 1)
                                            <span class="text-muted">{{ $opts[0]['testo'] ?? '' }}</span>
                                        @else
                                            <ul class="mb-0 ps-3 text-muted">
                                                @foreach ($opts as $o)
                                                    <li>{{ $o['testo'] ?? '' }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        </div>
    @endif
</section>
