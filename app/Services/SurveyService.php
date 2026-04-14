<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SurveyPrivacyMode;
use App\Models\Domanda;
use App\Support\SurveyTakePrivacyNotice;
use App\Models\Opzione;
use App\Models\Risposta;
use App\Models\Sondaggio;
use App\Models\User;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SurveyService
{
    /**
     * @param  array<int, array{text: string, type: string, options: array<int, string>}>  $questions
     * @param  array<int, int>  $tagIds
     */
    public function create(
        User $author,
        string $title,
        string $description,
        bool $isPublic,
        SurveyPrivacyMode $privacyMode,
        array $questions,
        ?CarbonInterface $dataScadenza,
        array $tagIds
    ): Sondaggio {
        return DB::transaction(function () use ($author, $title, $description, $isPublic, $privacyMode, $questions, $dataScadenza, $tagIds): Sondaggio {
            $survey = Sondaggio::query()->create([
                'titolo' => $title,
                'descrizione' => $description !== '' ? $description : null,
                'autore_id' => $author->id,
                'is_pubblico' => $isPublic,
                'privacy_mode' => $privacyMode,
                'data_scadenza' => $dataScadenza,
                'access_token' => Sondaggio::generateUniqueAccessToken(),
            ]);
            $this->insertQuestions($survey, $questions);
            $survey->tags()->sync($tagIds);

            return $survey->fresh(['domande.opzioni', 'tags']);
        });
    }

    /**
     * @param  array<int, array{text: string, type: string, options: array<int, string>}>  $questions
     * @param  array<int, int>  $tagIds
     */
    public function update(
        Sondaggio $survey,
        string $title,
        string $description,
        bool $isPublic,
        SurveyPrivacyMode $privacyMode,
        array $questions,
        ?CarbonInterface $dataScadenza,
        array $tagIds
    ): void {
        DB::transaction(function () use ($survey, $title, $description, $isPublic, $privacyMode, $questions, $dataScadenza, $tagIds): void {
            $hasResponses = Risposta::query()->where('sondaggio_id', $survey->id)->exists();
            if ($hasResponses && $survey->privacy_mode !== $privacyMode) {
                throw ValidationException::withMessages([
                    'privacy_mode' => 'Non puoi modificare la privacy del sondaggio: esistono già risposte.',
                ]);
            }

            $survey->update([
                'titolo' => $title,
                'descrizione' => $description !== '' ? $description : null,
                'is_pubblico' => $isPublic,
                'privacy_mode' => $privacyMode,
                'data_scadenza' => $dataScadenza,
            ]);
            if ($hasResponses) {
                $this->updateQuestionsInPlacePreservingIds($survey, $questions);
            } else {
                Domanda::query()->where('sondaggio_id', $survey->id)->delete();
                $this->insertQuestions($survey->fresh(), $questions);
            }
            $survey->tags()->sync($tagIds);
        });
    }

    /**
     * @param  array<int, array{text: string, type: string, options: array<int, string>}>  $questions
     */
    private function updateQuestionsInPlacePreservingIds(Sondaggio $survey, array $questions): void
    {
        $survey->load(['domande.opzioni']);
        $domande = $survey->domande->sortBy('ordine')->values();
        if ($domande->count() !== count($questions)) {
            throw ValidationException::withMessages([
                'questions' => 'Non puoi aggiungere o rimuovere domande: esistono già risposte. Modifica solo i testi.',
            ]);
        }
        foreach ($domande as $i => $domanda) {
            $q = $questions[$i];
            if ($domanda->tipo !== $q['type']) {
                throw ValidationException::withMessages([
                    'questions' => 'Non puoi cambiare il tipo di una domanda: esistono già risposte.',
                ]);
            }
            $opts = $domanda->opzioni->sortBy('ordine')->values();
            if ($opts->count() !== count($q['options'])) {
                throw ValidationException::withMessages([
                    'questions' => 'Non puoi aggiungere o rimuovere opzioni: esistono già risposte. Modifica solo i testi.',
                ]);
            }
            $domanda->update(['testo' => $q['text']]);
            foreach ($opts as $oi => $opzione) {
                $opzione->update(['testo' => $q['options'][$oi]]);
            }
        }
    }

    public function delete(Sondaggio $survey): void
    {
        $survey->delete();
    }

    /**
     * @param  array<int, array{text: string, type: string, options: array<int, string>}>  $questions
     */
    private function insertQuestions(Sondaggio $survey, array $questions): void
    {
        foreach ($questions as $qIndex => $question) {
            $domanda = Domanda::query()->create([
                'sondaggio_id' => $survey->id,
                'testo' => $question['text'],
                'tipo' => $question['type'],
                'ordine' => $qIndex + 1,
            ]);
            foreach ($question['options'] as $oIndex => $optionText) {
                Opzione::query()->create([
                    'domanda_id' => $domanda->id,
                    'testo' => $optionText,
                    'ordine' => $oIndex + 1,
                ]);
            }
        }
    }

    public function loadWithQuestions(Sondaggio $sondaggio): Sondaggio
    {
        return $sondaggio->load(['autore', 'domande.opzioni', 'tags']);
    }

    /**
     * @return array{survey_count: int, total_participations: int}
     */
    public function dashboardStatsForAuthor(int $authorId): array
    {
        $surveyCount = Sondaggio::query()->where('autore_id', $authorId)->count();
        $totalParticipations = DB::table('risposte as r')
            ->join('sondaggi as s', 's.id', '=', 'r.sondaggio_id')
            ->where('s.autore_id', $authorId)
            ->count();

        return [
            'survey_count' => $surveyCount,
            'total_participations' => $totalParticipations,
        ];
    }

    /**
     * @return array{total_responses: int, questions: array}
     */
    public function statsBySurvey(int $surveyId): array
    {
        $questions = Domanda::query()
            ->where('sondaggio_id', $surveyId)
            ->orderBy('ordine')
            ->get(['id', 'testo', 'tipo']);

        $totalResponses = DB::table('risposte as r')
            ->where('r.sondaggio_id', $surveyId)
            ->whereExists(function ($q): void {
                $q->selectRaw('1')
                    ->from('dettaglio_risposte as d')
                    ->whereColumn('d.risposta_id', 'r.id');
            })
            ->count();

        $outQuestions = [];
        foreach ($questions as $question) {
            $options = DB::table('opzioni as o')
                ->leftJoin('dettaglio_risposte as dr', 'dr.opzione_id', '=', 'o.id')
                ->where('o.domanda_id', $question->id)
                ->groupBy('o.id', 'o.testo', 'o.ordine')
                ->orderBy('o.ordine')
                ->selectRaw('o.id, o.testo, COALESCE(COUNT(dr.id), 0) as votes')
                ->get();

            $base = max($totalResponses, 1);
            $opts = [];
            foreach ($options as $option) {
                $votes = (int) $option->votes;
                $opts[] = [
                    'id' => (int) $option->id,
                    'testo' => $option->testo,
                    'votes' => $votes,
                    'percentuale' => round(($votes / $base) * 100, 2),
                ];
            }
            $outQuestions[] = [
                'id' => $question->id,
                'testo' => $question->testo,
                'tipo' => $question->tipo,
                'options' => $opts,
            ];
        }

        return [
            'total_responses' => $totalResponses,
            'questions' => $outQuestions,
        ];
    }

    /**
     * Dati partecipanti per il creatore in pagina statistiche (e in seguito PDF). Chiamare solo dopo `authorize('viewStats', $sondaggio)`.
     *
     * Evoluzione consigliata: paginazione / lazy load sulla lista; endpoint dedicato tipo
     * `GET .../statistiche/partecipanti?q=` con stesso vincolo di policy e filtro server-side
     * quando i volumi crescono (oggi `searchQuery` filtra in memoria solo come anticipazione).
     *
     * @return array{
     *     mode: string,
     *     shows_participant_list: bool,
     *     shows_individual_answers: bool,
     *     anonymous_explanation: string|null,
     *     participants_grand_total: int,
     *     participants_truncated: bool,
     *     participant_count: int,
     *     participants: list<array{
     *         response_id: int,
     *         data_compilazione: string,
     *         user: array{id: int, nome: string, email: string}|null,
     *         answers: list<array{domanda_id: int, domanda_testo: string, opzioni: list<array{id: int, testo: string}>}>|null
     *     }>
     * }
     */
    public function participantInsightsForCreator(Sondaggio $survey, ?string $searchQuery = null, ?int $participantLimit = null): array
    {
        $privacy = $survey->privacy_mode ?? SurveyPrivacyMode::IdentifiedFull;

        if ($privacy === SurveyPrivacyMode::Anonymous) {
            return [
                'mode' => $privacy->value,
                'shows_participant_list' => false,
                'shows_individual_answers' => false,
                'anonymous_explanation' => 'Per impostazione privacy del sondaggio le compilazioni non sono associate agli account: qui sono disponibili solo le statistiche aggregate.',
                'participants_grand_total' => 0,
                'participants_truncated' => false,
                'participant_count' => 0,
                'participants' => [],
            ];
        }

        $survey->loadMissing('domande');

        // Eager load mirato: evita N+1 nel payload (web e PDF).
        $responses = Risposta::query()
            ->where('sondaggio_id', $survey->id)
            ->whereNotNull('utente_id')
            ->whereHas('dettagli')
            ->with(['utente', 'dettagli.opzione', 'dettagli.domanda'])
            ->orderBy('data_compilazione')
            ->orderBy('id')
            ->get();

        $showsAnswers = $privacy === SurveyPrivacyMode::IdentifiedFull;
        $participants = [];

        foreach ($responses as $risposta) {
            $answers = $showsAnswers
                ? $this->participantAnswersPayload($survey, $risposta)
                : null;

            $utente = $risposta->utente;
            $participants[] = [
                'response_id' => (int) $risposta->id,
                'data_compilazione' => $risposta->data_compilazione instanceof CarbonInterface
                    ? $risposta->data_compilazione->timezone(config('app.timezone'))->format(DateTimeInterface::ATOM)
                    : '',
                'user' => $utente !== null
                    ? [
                        'id' => (int) $utente->id,
                        'nome' => $utente->nome,
                        'email' => $utente->email,
                    ]
                    : null,
                'answers' => $answers,
            ];
        }

        $participantsGrandTotal = count($participants);
        $truncated = false;
        if ($participantLimit !== null && $participantLimit > 0 && $participantsGrandTotal > $participantLimit) {
            $participants = array_slice($participants, 0, $participantLimit);
            $truncated = true;
        }

        $needle = $searchQuery !== null ? mb_strtolower(trim($searchQuery)) : '';
        if ($needle !== '') {
            $participants = array_values(array_filter(
                $participants,
                static function (array $row) use ($needle): bool {
                    $user = $row['user'];
                    if ($user === null) {
                        return false;
                    }
                    $hayNome = mb_strtolower((string) $user['nome']);
                    $hayEmail = mb_strtolower((string) $user['email']);

                    return str_contains($hayNome, $needle) || str_contains($hayEmail, $needle);
                }
            ));
        }

        return [
            'mode' => $privacy->value,
            'shows_participant_list' => true,
            'shows_individual_answers' => $showsAnswers,
            'anonymous_explanation' => null,
            'participants_grand_total' => $participantsGrandTotal,
            'participants_truncated' => $truncated,
            'participant_count' => count($participants),
            'participants' => $participants,
        ];
    }

    /**
     * @return list<array{domanda_id: int, domanda_testo: string, opzioni: list<array{id: int, testo: string}>}>
     */
    private function participantAnswersPayload(Sondaggio $survey, Risposta $risposta): array
    {
        $byDomandaId = $risposta->dettagli->groupBy('domanda_id');
        $out = [];

        foreach ($survey->domande as $domanda) {
            /** @var Collection<int, \App\Models\DettaglioRisposta> $rows */
            $rows = $byDomandaId->get($domanda->id, collect());
            if ($rows->isEmpty()) {
                continue;
            }

            $opzioni = [];
            foreach ($rows->sortBy(fn ($d) => $d->opzione?->ordine ?? 0)->values() as $det) {
                $opt = $det->opzione;
                if ($opt !== null) {
                    $opzioni[] = [
                        'id' => (int) $opt->id,
                        'testo' => $opt->testo,
                    ];
                }
            }

            if ($opzioni === []) {
                continue;
            }

            $out[] = [
                'domanda_id' => (int) $domanda->id,
                'domanda_testo' => $domanda->testo,
                'opzioni' => $opzioni,
            ];
        }

        return $out;
    }

    /**
     * Payload per la vista sondaggio chiuso (`surveys.take-closed`), allineato alle chiavi usate in quella Blade.
     *
     * @return array{id: int, titolo: string, data_scadenza_label: ?string, tags: array<int, array{id: int, nome: string}>, privacy_mode: string, privacy_notice: array{heading: string, body: string}}
     */
    public function toClosedSurveyViewArray(Sondaggio $survey): array
    {
        $survey->loadMissing('tags');

        $dataScadenzaLabel = $survey->data_scadenza
            ? $survey->data_scadenza->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : null;

        $privacyMode = $survey->privacy_mode ?? SurveyPrivacyMode::IdentifiedFull;

        return [
            'id' => (int) $survey->id,
            'titolo' => $survey->titolo,
            'data_scadenza_label' => $dataScadenzaLabel,
            'tags' => $survey->tags->map(fn ($t) => ['id' => $t->id, 'nome' => $t->nome])->values()->all(),
            'privacy_mode' => $privacyMode->value,
            'privacy_notice' => SurveyTakePrivacyNotice::forMode($privacyMode),
        ];
    }

    /**
     * Payload per la vista compilazione (`surveys.take`).
     *
     * @return array{id: int, access_token: string, titolo: string, descrizione: ?string, is_pubblico: int, tags: list<array{id: int, nome: string}>, questions: list<array<string, mixed>>, data_scadenza_label: ?string, is_scaduto: bool, privacy_mode: string, privacy_notice: array{heading: string, body: string}}
     */
    public function toTakeViewArray(Sondaggio $survey): array
    {
        $questions = [];
        foreach ($survey->domande as $q) {
            $questions[] = [
                'id' => $q->id,
                'testo' => $q->testo,
                'tipo' => $q->tipo,
                'options' => $q->opzioni->map(fn ($o) => ['id' => $o->id, 'testo' => $o->testo])->all(),
            ];
        }

        $dataScadenzaLabel = $survey->data_scadenza
            ? $survey->data_scadenza->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : null;

        $privacyMode = $survey->privacy_mode ?? SurveyPrivacyMode::IdentifiedFull;

        return [
            'id' => $survey->id,
            'access_token' => $survey->access_token,
            'titolo' => $survey->titolo,
            'descrizione' => $survey->descrizione,
            'is_pubblico' => $survey->is_pubblico ? 1 : 0,
            'tags' => $survey->tags->map(fn ($t) => ['id' => $t->id, 'nome' => $t->nome])->values()->all(),
            'questions' => $questions,
            'data_scadenza_label' => $dataScadenzaLabel,
            'is_scaduto' => $survey->isScaduto(),
            'privacy_mode' => $privacyMode->value,
            'privacy_notice' => SurveyTakePrivacyNotice::forMode($privacyMode),
        ];
    }
}
