<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Report — {{ $survey->titolo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1a1a1a; line-height: 1.35; margin: 24px; }
        h1 { font-size: 16pt; margin: 0 0 8px 0; font-weight: bold; }
        .meta { font-size: 9pt; color: #444; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 12px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 8px 3px 0; vertical-align: top; }
        .meta td:first-child { font-weight: bold; width: 140px; color: #333; }
        .section-title { font-size: 12pt; font-weight: bold; margin: 18px 0 8px 0; }
        .section-sub { font-size: 9.5pt; color: #555; margin: -4px 0 12px 0; line-height: 1.4; }
        .descr { margin: 0 0 14px 0; color: #333; }
        .status { display: inline-block; padding: 2px 8px; font-size: 9pt; border-radius: 3px; margin-bottom: 14px; }
        .status--open { background: #e8f4e8; color: #1b5e20; }
        .status--closed { background: #f0f0f0; color: #424242; }
        .q-block { margin-bottom: 18px; page-break-inside: avoid; }
        .q-title { font-weight: bold; margin: 0 0 6px 0; }
        .q-type { font-size: 9pt; color: #555; margin-bottom: 6px; }
        table.data { width: 100%; border-collapse: collapse; font-size: 9.5pt; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #bbb; padding: 6px 8px; text-align: left; word-wrap: break-word; overflow-wrap: break-word; vertical-align: top; }
        table.data th { background: #f5f5f5; font-weight: bold; }
        table.data td.num { text-align: right; white-space: nowrap; }
        table.data tr.pdf-participant-row { page-break-inside: avoid; }
        .pdf-participant-block { page-break-inside: avoid; margin-bottom: 16px; border: 1px solid #ccc; padding: 10px 12px; border-radius: 2px; }
        .pdf-participant-block__title { font-weight: bold; margin: 0 0 8px 0; font-size: 10pt; }
        .pdf-meta-table { width: 100%; font-size: 9.5pt; margin-bottom: 10px; }
        .pdf-meta-table td { padding: 2px 8px 2px 0; vertical-align: top; }
        .pdf-meta-table td:first-child { font-weight: bold; width: 100px; color: #444; }
        .pdf-truncation-note { font-size: 9pt; color: #444; background: #f9f9f9; padding: 8px 10px; border: 1px solid #ddd; margin: 0 0 12px 0; line-height: 1.35; }
        .pdf-empty-participants { font-size: 9.5pt; color: #555; margin: 0 0 8px 0; }
        .footer { margin-top: 28px; font-size: 8.5pt; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <h1>{{ $survey->titolo }}</h1>

    <div class="meta">
        <table>
            <tr>
                <td>Generato il</td>
                <td>{{ $generated_at }} ({{ config('app.timezone') }})</td>
            </tr>
            <tr>
                <td>ID sondaggio</td>
                <td>{{ $survey->id }}</td>
            </tr>
            <tr>
                <td>Autore</td>
                <td>
                    @if($author)
                        {{ $author->nome }} — {{ $author->email }}
                    @else
                        —
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <span class="status {{ $is_scaduto ? 'status--closed' : 'status--open' }}">
        {{ $is_scaduto ? 'Sondaggio chiuso' : 'Sondaggio attivo' }}
    </span>

    @if(filled($survey->descrizione))
        <p class="descr">{{ $survey->descrizione }}</p>
    @endif

    <p class="section-title">Riepilogo compilazioni</p>
    <p style="margin: 0 0 16px 0;"><strong>Compilazioni totali:</strong> {{ (int) $stats['total_responses'] }}</p>

    @if((int) $stats['total_responses'] === 0)
        <p style="color: #555;">Nessuna compilazione con risposte registrate.</p>
    @else
        @foreach($stats['questions'] as $index => $question)
            <div class="q-block">
                <p class="q-title">Domanda {{ $index + 1 }} — {{ $question['testo'] }}</p>
                <p class="q-type">
                    Tipo: {{ ($question['tipo'] ?? '') === 'multipla' ? 'Scelta multipla' : 'Scelta singola' }}
                </p>
                <table class="data">
                    <thead>
                        <tr>
                            <th>Opzione</th>
                            <th class="num">Voti</th>
                            <th class="num">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($question['options'] as $option)
                            <tr>
                                <td>{{ $option['testo'] }}</td>
                                <td class="num">{{ (int) $option['votes'] }}</td>
                                <td class="num">{{ $option['percentuale'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    @php
        /** @var array $participant_insights */
        use Illuminate\Support\Carbon;
        $pi = $participant_insights ?? [];
        $showParticipantSection = (bool) ($pi['shows_participant_list'] ?? false);
        $showsAnswersPdf = (bool) ($pi['shows_individual_answers'] ?? false);
        $pdfParticipantCount = (int) ($pi['participant_count'] ?? 0);
        $pdfParticipants = $pi['participants'] ?? [];
        $pdfTruncated = (bool) ($pi['participants_truncated'] ?? false);
        $pdfGrandTotal = (int) ($pi['participants_grand_total'] ?? 0);
        $pdfMax = (int) config('sondaggi.stats_pdf_max_participants', 500);
    @endphp

    @if($showParticipantSection)
        <p class="section-title">Partecipanti</p>
        @if($showsAnswersPdf)
            <p class="section-sub">Elenco con dettaglio delle risposte individuali (come in pagina statistiche). Ordine: data di compilazione, poi ID risposta.</p>
        @else
            <p class="section-sub">Elenco nominale senza testo delle opzioni scelte: le risposte restano registrate nel sistema ma non sono incluse in questo report per impostazione privacy del sondaggio.</p>
        @endif

        @if($pdfTruncated && $pdfMax > 0)
            <div class="pdf-truncation-note" role="note">
                Nel report sono inclusi i primi <strong>{{ $pdfMax }}</strong> partecipanti (su <strong>{{ $pdfGrandTotal }}</strong> totali), ordinati per data di compilazione.
                Per l’elenco completo utilizza la pagina statistiche sul sito.
            </div>
        @endif

        @if($pdfParticipantCount === 0)
            <p class="pdf-empty-participants">Nessun partecipante con account collegato da includere in questa sezione.</p>
        @elseif($showsAnswersPdf)
            @foreach($pdfParticipants as $p)
                @php
                    $u = $p['user'] ?? null;
                    $nomeDisp = ($u['nome'] ?? '') !== '' ? $u['nome'] : '—';
                    $emailDisp = ($u['email'] ?? '') !== '' ? $u['email'] : '—';
                    $compiledLabel = '—';
                    if (! empty($p['data_compilazione'])) {
                        try {
                            $compiledLabel = Carbon::parse($p['data_compilazione'])->timezone(config('app.timezone'))->format('d/m/Y H:i');
                        } catch (\Throwable) {
                            $compiledLabel = '—';
                        }
                    }
                @endphp
                <div class="pdf-participant-block">
                    <p class="pdf-participant-block__title">{{ $nomeDisp }} — {{ $emailDisp }}</p>
                    <table class="pdf-meta-table">
                        <tr>
                            <td>Compilazione</td>
                            <td>{{ $compiledLabel }}</td>
                        </tr>
                    </table>
                    <p style="font-size: 9.5pt; font-weight: bold; margin: 0 0 6px 0;">Risposte</p>
                    <table class="data">
                        <thead>
                            <tr>
                                <th style="width: 38%;">Domanda</th>
                                <th>Risposta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($p['answers'] ?? [] as $ans)
                                @php
                                    $opts = $ans['opzioni'] ?? [];
                                    $answerText = '';
                                    if (count($opts) === 1) {
                                        $answerText = (string) ($opts[0]['testo'] ?? '');
                                    } else {
                                        $answerText = collect($opts)->pluck('testo')->filter()->implode('; ');
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $ans['domanda_testo'] ?? '' }}</td>
                                    <td>{{ $answerText }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 36px;">#</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Compilazione</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pdfParticipants as $p)
                        @php
                            $u = $p['user'] ?? null;
                            $nomeDisp = ($u['nome'] ?? '') !== '' ? $u['nome'] : '—';
                            $emailDisp = ($u['email'] ?? '') !== '' ? $u['email'] : '—';
                            $compiledLabel = '—';
                            if (! empty($p['data_compilazione'])) {
                                try {
                                    $compiledLabel = Carbon::parse($p['data_compilazione'])->timezone(config('app.timezone'))->format('d/m/Y H:i');
                                } catch (\Throwable) {
                                    $compiledLabel = '—';
                                }
                            }
                        @endphp
                        <tr class="pdf-participant-row">
                            <td class="num">{{ $loop->iteration }}</td>
                            <td>{{ $nomeDisp }}</td>
                            <td>{{ $emailDisp }}</td>
                            <td>{{ $compiledLabel }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <div class="footer">
        Documento generato automaticamente da {{ config('app.name') }}.
    </div>
</body>
</html>
