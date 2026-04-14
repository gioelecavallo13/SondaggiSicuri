<?php

namespace App\Http\Controllers;

use App\Models\Sondaggio;
use App\Services\ResponseSubmissionService;
use App\Services\SurveyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ResponseController extends Controller
{
    public function __construct(
        private readonly ResponseSubmissionService $responseSubmission,
        private readonly SurveyService $surveyService,
    ) {}

    public function submit(Request $request, Sondaggio $sondaggio): View|RedirectResponse|Response
    {
        $sondaggio->load(['domande.opzioni', 'tags']);

        if ($sondaggio->isScaduto()) {
            $user = $request->user();
            if ($user === null) {
                abort(403);
            }

            $result = $this->responseSubmission->submitAuthenticated($sondaggio, $request, $user, []);

            return view('surveys.take-closed', [
                'closed' => $this->surveyService->toClosedSurveyViewArray($sondaggio),
                'closedErrors' => $result['errors'],
            ]);
        }

        $validation = $this->responseSubmission->validateAnswers($sondaggio, $request->input('answers', []));
        if ($validation['errors'] !== []) {
            return $this->takeViewWithErrors($sondaggio, $validation['errors']);
        }

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $result = $this->responseSubmission->submitAuthenticated($sondaggio, $request, $user, $validation['normalized']);
        if (! $result['ok']) {
            return $this->takeViewWithErrors($sondaggio, $result['errors']);
        }

        $response = response()->view('surveys.thanks');
        $cookieClientId = $result['anonymous_client_id_for_cookie'] ?? null;
        if ($cookieClientId !== null && $cookieClientId !== '' && $sondaggio->isPrivacyAnonymous()) {
            $response->cookie($this->anonymousVoteCookie($sondaggio, $cookieClientId));
        }

        return $response;
    }

    /**
     * Cookie anti-duplicato solo sulla risposta POST di invio riuscito (mai sulla sola GET della take).
     */
    private function anonymousVoteCookie(Sondaggio $survey, string $clientId): \Symfony\Component\HttpFoundation\Cookie
    {
        $minutes = $this->anonymousVoteCookieMinutes($survey);

        return cookie(
            (string) config('sondaggi.anonymous_vote_cookie'),
            $clientId,
            $minutes,
            '/',
            null,
            (bool) config('session.secure'),
            true,
            false,
            (string) (config('session.same_site') ?? 'lax')
        );
    }

    private function anonymousVoteCookieMinutes(Sondaggio $survey): int
    {
        $maxSeconds = max(3600, (int) config('sondaggi.anonymous_vote_cookie_max_age_seconds', 31536000));
        $expiresAt = now()->addSeconds($maxSeconds);
        if (config('sondaggi.anonymous_vote_cookie_cap_at_survey_expiry', true)
            && $survey->data_scadenza !== null
            && $survey->data_scadenza->isFuture()) {
            $cap = $survey->data_scadenza->copy();
            if ($cap->lessThan($expiresAt)) {
                $expiresAt = $cap;
            }
        }
        $seconds = max(60, $expiresAt->getTimestamp() - now()->getTimestamp());

        return max(1, (int) ceil($seconds / 60));
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function takeViewWithErrors(Sondaggio $sondaggio, array $errors): View
    {
        $sondaggio->load(['domande.opzioni', 'tags']);

        return view('surveys.take', [
            'survey' => $this->surveyService->toTakeViewArray($sondaggio),
            'takeErrors' => $errors,
        ]);
    }
}
