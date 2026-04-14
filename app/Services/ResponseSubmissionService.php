<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SurveyPrivacyMode;
use App\Models\DettaglioRisposta;
use App\Models\Risposta;
use App\Models\Sondaggio;
use App\Models\SurveySubmitAttempt;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResponseSubmissionService
{
    /**
     * @param  array<string, mixed>  $answersInput
     * @return array{errors: array<int, string>, normalized: array<int, array<int>>}
     */
    public function validateAnswers(Sondaggio $survey, array $answersInput): array
    {
        $survey->loadMissing('domande.opzioni');
        $errors = [];
        $normalizedAnswers = [];

        if (! is_array($answersInput)) {
            return ['errors' => ['Risposte non valide.'], 'normalized' => []];
        }

        foreach ($survey->domande as $question) {
            $qid = (int) $question->id;
            $selected = $answersInput[$qid] ?? null;
            $selectedIds = is_array($selected) ? array_map('intval', $selected) : [intval((string) $selected)];
            $selectedIds = array_values(array_filter($selectedIds, fn ($id) => $id > 0));

            if (count($selectedIds) === 0) {
                $errors[] = 'Rispondi a tutte le domande.';
                break;
            }
            if ($question->tipo === 'singola' && count($selectedIds) > 1) {
                $errors[] = 'Una domanda singola ha più opzioni selezionate.';
                break;
            }

            $validOptionIds = $question->opzioni->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach ($selectedIds as $sid) {
                if (! in_array($sid, $validOptionIds, true)) {
                    $errors[] = 'Risposta non valida.';
                    break 2;
                }
            }

            $normalizedAnswers[$qid] = array_values(array_unique($selectedIds));
        }

        return ['errors' => $errors, 'normalized' => $normalizedAnswers];
    }

    /**
     * Ordinamento elenco sondaggi pubblici: non partecipati prima, già risposti in coda (stessa logica di {@see self::participatedSurveyIdsForRequest}).
     *
     * @param  Builder<Sondaggio>  $builder
     */
    public function applyPublicSurveyListParticipationOrdering(Builder $builder, Request $request): void
    {
        $anonymous = SurveyPrivacyMode::Anonymous->value;
        $userId = $request->user()?->id;
        $cookieName = (string) config('sondaggi.anonymous_vote_cookie');
        $rawCookie = $request->cookie($cookieName);
        $clientUuid = is_string($rawCookie) && Str::isUuid($rawCookie) ? $rawCookie : null;
        $fingerprint = $this->requestFingerprint($request);

        $builder->orderByRaw(
            '(
                EXISTS (
                    SELECT 1 FROM risposte r
                    WHERE r.sondaggio_id = sondaggi.id
                    AND (
                        (
                            sondaggi.privacy_mode = ?
                            AND r.utente_id IS NULL
                            AND (
                                (? IS NOT NULL AND r.client_id = ?)
                                OR r.session_fingerprint = ?
                            )
                        )
                        OR (
                            sondaggi.privacy_mode <> ?
                            AND ? IS NOT NULL
                            AND r.utente_id = ?
                        )
                    )
                )
            ) ASC, sondaggi.id DESC',
            [
                $anonymous,
                $clientUuid,
                $clientUuid,
                $fingerprint,
                $anonymous,
                $userId,
                $userId,
            ]
        );
    }

    /**
     * @param  array<int, int>  $surveyIds
     * @return list<int>
     */
    public function participatedSurveyIdsForRequest(Request $request, array $surveyIds): array
    {
        $surveyIds = array_values(array_unique(array_filter($surveyIds, fn ($id) => (int) $id > 0)));
        if ($surveyIds === []) {
            return [];
        }

        $surveys = Sondaggio::query()->whereIn('id', $surveyIds)->get(['id', 'privacy_mode']);
        $anonIds = [];
        $identifiedIds = [];
        foreach ($surveys as $s) {
            if ($s->isPrivacyAnonymous()) {
                $anonIds[] = $s->id;
            } else {
                $identifiedIds[] = $s->id;
            }
        }

        $out = [];

        $userId = $request->user()?->id;
        if ($userId !== null && $identifiedIds !== []) {
            $rows = Risposta::query()
                ->whereIn('sondaggio_id', $identifiedIds)
                ->where('utente_id', $userId)
                ->pluck('sondaggio_id');
            foreach ($rows as $sid) {
                $out[] = (int) $sid;
            }
        }

        if ($anonIds !== []) {
            $cookieName = (string) config('sondaggi.anonymous_vote_cookie');
            $rawCookie = $request->cookie($cookieName);
            $clientFromCookie = is_string($rawCookie) && Str::isUuid($rawCookie) ? $rawCookie : null;
            $fingerprint = $this->requestFingerprint($request);

            $rows = Risposta::query()
                ->whereIn('sondaggio_id', $anonIds)
                ->whereNull('utente_id')
                ->where(function ($q) use ($clientFromCookie, $fingerprint): void {
                    if ($clientFromCookie !== null) {
                        $q->where('client_id', $clientFromCookie);
                    }
                    $q->orWhere('session_fingerprint', $fingerprint);
                })
                ->pluck('sondaggio_id');
            foreach ($rows as $sid) {
                $out[] = (int) $sid;
            }
        }

        return array_values(array_unique($out));
    }

    public function viewerHasResponded(Sondaggio $survey, Request $request): bool
    {
        return in_array((int) $survey->id, $this->participatedSurveyIdsForRequest($request, [(int) $survey->id]), true);
    }

    public function hasResponseForUser(int $surveyId, int $userId): bool
    {
        return Risposta::query()
            ->where('sondaggio_id', $surveyId)
            ->where('utente_id', $userId)
            ->exists();
    }

    public function hasResponseForAnonymousClient(int $surveyId, string $clientUuid): bool
    {
        return Risposta::query()
            ->where('sondaggio_id', $surveyId)
            ->where('client_id', $clientUuid)
            ->exists();
    }

    /**
     * Fallback se il browser non invia il cookie: dedup su fingerprint (IP + User-Agent + Accept-Language).
     * Limite: cambiando rete o UA l’utente può inviare una seconda risposta; accettabile come best-effort.
     */
    public function hasResponseForAnonymousFingerprint(int $surveyId, string $fingerprint): bool
    {
        return Risposta::query()
            ->where('sondaggio_id', $surveyId)
            ->whereNull('utente_id')
            ->where('session_fingerprint', $fingerprint)
            ->exists();
    }

    public function countRecentSubmitAttempts(int $surveyId, string $ipHash, int $windowSeconds): int
    {
        return SurveySubmitAttempt::query()
            ->where('sondaggio_id', $surveyId)
            ->where('ip_hash', $ipHash)
            ->where('attempted_at', '>=', now()->subSeconds($windowSeconds))
            ->count();
    }

    public function recordSubmitAttempt(int $surveyId, string $ipHash): void
    {
        SurveySubmitAttempt::query()->create([
            'sondaggio_id' => $surveyId,
            'ip_hash' => $ipHash,
            'attempted_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array<int>>  $normalizedAnswers  questionId => optionIds
     */
    public function saveResponse(
        int $surveyId,
        ?int $userId,
        array $normalizedAnswers,
        ?string $fingerprint,
        ?string $clientId,
        ?string $ipHash
    ): void {
        DB::transaction(function () use ($surveyId, $userId, $normalizedAnswers, $fingerprint, $clientId, $ipHash): void {
            $risposta = Risposta::query()->create([
                'utente_id' => $userId,
                'sondaggio_id' => $surveyId,
                'session_fingerprint' => $fingerprint,
                'client_id' => $clientId,
                'ip_hash' => $ipHash,
            ]);

            foreach ($normalizedAnswers as $questionId => $optionIds) {
                foreach ($optionIds as $optionId) {
                    DettaglioRisposta::query()->create([
                        'risposta_id' => $risposta->id,
                        'domanda_id' => (int) $questionId,
                        'opzione_id' => (int) $optionId,
                    ]);
                }
            }
        });
    }

    public function requestFingerprint(Request $request): string
    {
        return hash(
            'sha256',
            $request->ip().'|'.($request->userAgent() ?? '').'|'.($request->header('Accept-Language') ?? '')
        );
    }

    public function requestIpHash(Request $request): string
    {
        $salt = (string) config('sondaggi.response_ip_salt');

        return hash('sha256', $salt.'|'.$request->ip());
    }

    /**
     * Invio risposte solo per utente autenticato (sondaggi pubblici e privati).
     *
     * @param  array<int, array<int>>  $normalizedAnswers
     * @return array{ok: bool, errors: array<int, string>, anonymous_client_id_for_cookie: ?string}
     */
    public function submitAuthenticated(Sondaggio $survey, Request $request, Authenticatable $user, array $normalizedAnswers): array
    {
        if ($survey->isScaduto()) {
            $label = $survey->data_scadenza->timezone(config('app.timezone'))->format('d/m/Y H:i');

            return [
                'ok' => false,
                'errors' => [
                    "Questo sondaggio non accetta più risposte (scadenza: {$label}).",
                ],
                'anonymous_client_id_for_cookie' => null,
            ];
        }

        $window = (int) config('sondaggi.rate_limit_window_seconds', 900);
        $maxAttempts = (int) config('sondaggi.rate_limit_max_attempts', 30);

        $ipHash = $this->requestIpHash($request);

        if ($this->countRecentSubmitAttempts($survey->id, $ipHash, $window) >= $maxAttempts) {
            return [
                'ok' => false,
                'errors' => ['Troppi tentativi di invio da questa rete. Riprova più tardi.'],
                'anonymous_client_id_for_cookie' => null,
            ];
        }

        $this->recordSubmitAttempt($survey->id, $ipHash);

        $fingerprint = $this->requestFingerprint($request);

        if ($survey->isPrivacyAnonymous()) {
            $cookieName = (string) config('sondaggi.anonymous_vote_cookie');
            $rawCookie = $request->cookie($cookieName);
            $clientFromCookie = is_string($rawCookie) && Str::isUuid($rawCookie) ? $rawCookie : null;

            if ($clientFromCookie !== null) {
                if ($this->hasResponseForAnonymousClient($survey->id, $clientFromCookie)) {
                    return [
                        'ok' => false,
                        'errors' => ['Hai già inviato una risposta per questo sondaggio.'],
                        'anonymous_client_id_for_cookie' => null,
                    ];
                }
                $clientIdToStore = $clientFromCookie;
            } elseif ($this->hasResponseForAnonymousFingerprint($survey->id, $fingerprint)) {
                return [
                    'ok' => false,
                    'errors' => ['Hai già inviato una risposta per questo sondaggio.'],
                    'anonymous_client_id_for_cookie' => null,
                ];
            } else {
                $clientIdToStore = Str::uuid()->toString();
            }

            $this->saveResponse($survey->id, null, $normalizedAnswers, $fingerprint, $clientIdToStore, $ipHash);

            return [
                'ok' => true,
                'errors' => [],
                'anonymous_client_id_for_cookie' => $clientIdToStore,
            ];
        }

        $userId = (int) $user->getAuthIdentifier();
        if ($this->hasResponseForUser($survey->id, $userId)) {
            return [
                'ok' => false,
                'errors' => ['Hai già inviato una risposta per questo sondaggio.'],
                'anonymous_client_id_for_cookie' => null,
            ];
        }

        $this->saveResponse($survey->id, $userId, $normalizedAnswers, $fingerprint, null, $ipHash);

        return [
            'ok' => true,
            'errors' => [],
            'anonymous_client_id_for_cookie' => null,
        ];
    }
}
