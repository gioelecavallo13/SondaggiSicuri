<?php

namespace App\Http\Controllers;

use App\Enums\SurveyPrivacyMode;
use App\Models\Sondaggio;
use App\Models\Tag;
use App\Services\SurveyService;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function __construct(
        private readonly SurveyService $surveyService,
    ) {}

    public function dashboard(): View
    {
        $user = Auth::user();
        $surveys = Sondaggio::query()
            ->where('autore_id', $user->id)
            ->ordineScadutiInFondo()
            ->get();

        $dashboardStats = $this->surveyService->dashboardStatsForAuthor((int) $user->id);

        return view('surveys.dashboard', [
            'surveys' => $surveys,
            'dashboardStats' => $dashboardStats,
        ]);
    }

    public function createForm(): View
    {
        return view('surveys.form', [
            'survey' => null,
            'formErrors' => [],
            'allTags' => Tag::query()->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|View
    {
        $payload = $this->validateSurveyPayload($request->all());
        if ($payload['errors'] !== []) {
            return view('surveys.form', [
                'survey' => null,
                'formErrors' => $payload['errors'],
                'allTags' => Tag::query()->orderBy('nome')->get(),
            ]);
        }

        $this->surveyService->create(
            Auth::user(),
            $payload['title'],
            $payload['description'],
            $payload['is_public'],
            $payload['privacy_mode'],
            $payload['questions'],
            $payload['data_scadenza'],
            $payload['tag_ids']
        );

        return redirect()->route('dashboard');
    }

    public function editForm(Sondaggio $sondaggio): View
    {
        $this->authorize('update', $sondaggio);
        $survey = $this->surveyService->loadWithQuestions($sondaggio);
        $surveyData = $this->surveyToFormArray($survey);

        return view('surveys.form', [
            'survey' => $surveyData,
            'formErrors' => [],
            'allTags' => Tag::query()->orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request, Sondaggio $sondaggio): RedirectResponse|View
    {
        $this->authorize('update', $sondaggio);

        $payload = $this->validateSurveyPayload($request->all());
        if ($payload['errors'] !== []) {
            $survey = $this->surveyService->loadWithQuestions($sondaggio);

            return view('surveys.form', [
                'survey' => $this->surveyToFormArray($survey),
                'formErrors' => $payload['errors'],
                'allTags' => Tag::query()->orderBy('nome')->get(),
            ]);
        }

        $this->surveyService->update(
            $sondaggio,
            $payload['title'],
            $payload['description'],
            $payload['is_public'],
            $payload['privacy_mode'],
            $payload['questions'],
            $payload['data_scadenza'],
            $payload['tag_ids']
        );

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request, Sondaggio $sondaggio): RedirectResponse
    {
        $this->authorize('delete', $sondaggio);
        $this->surveyService->delete($sondaggio);

        return redirect()->route('dashboard');
    }

    public function show(Sondaggio $sondaggio): View
    {
        if ($sondaggio->isScaduto()) {
            $sondaggio->loadMissing('tags');

            return view('surveys.take-closed', [
                'closed' => $this->surveyService->toClosedSurveyViewArray($sondaggio),
                'closedErrors' => [],
            ]);
        }

        $survey = $this->surveyService->loadWithQuestions($sondaggio);

        return view('surveys.take', [
            'survey' => $this->surveyService->toTakeViewArray($survey),
            'takeErrors' => [],
        ]);
    }

    public function stats(Sondaggio $sondaggio): View
    {
        $this->authorize('viewStats', $sondaggio);
        $survey = $this->surveyService->loadWithQuestions($sondaggio);
        $stats = $this->surveyService->statsBySurvey($sondaggio->id);
        $participantInsights = $this->surveyService->participantInsightsForCreator($sondaggio, null);

        return view('surveys.stats', [
            'survey' => $this->surveyToFormArray($survey),
            'stats' => $stats,
            'participant_insights' => $participantInsights,
            'is_scaduto' => $sondaggio->isScaduto(),
            'stats_refresh_interval_seconds' => (int) config('surveys.stats_refresh_interval_seconds'),
        ]);
    }

    public function statsData(Sondaggio $sondaggio): JsonResponse
    {
        $this->authorize('viewStats', $sondaggio);

        return response()->json($this->surveyService->statsBySurvey($sondaggio->id));
    }

    public function statsReportPdf(Sondaggio $sondaggio): Response
    {
        $this->authorize('viewStats', $sondaggio);
        $sondaggio->loadMissing('autore');
        $survey = $this->surveyService->loadWithQuestions($sondaggio);
        $stats = $this->surveyService->statsBySurvey($sondaggio->id);
        $generatedAt = now()->timezone(config('app.timezone'))->format('d/m/Y H:i:s');
        $maxPdf = (int) config('sondaggi.stats_pdf_max_participants', 500);
        $pdfParticipantLimit = $maxPdf > 0 ? $maxPdf : null;
        $participantInsights = $this->surveyService->participantInsightsForCreator($sondaggio, null, $pdfParticipantLimit);

        /** @var PDF $pdf */
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('surveys.reports.stats-pdf', [
            'survey' => $survey,
            'stats' => $stats,
            'is_scaduto' => $sondaggio->isScaduto(),
            'generated_at' => $generatedAt,
            'author' => $sondaggio->autore,
            'participant_insights' => $participantInsights,
        ]);

        $filename = 'report-sondaggio-'.$sondaggio->id.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function surveyToFormArray(Sondaggio $survey): array
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

        $privacy = $survey->privacy_mode ?? SurveyPrivacyMode::IdentifiedFull;

        return [
            'id' => $survey->id,
            'access_token' => $survey->access_token,
            'titolo' => $survey->titolo,
            'descrizione' => $survey->descrizione,
            'is_pubblico' => $survey->is_pubblico ? 1 : 0,
            'privacy_mode' => $privacy instanceof SurveyPrivacyMode ? $privacy->value : (string) $privacy,
            'data_scadenza' => $survey->data_scadenza
                ? $survey->data_scadenza->timezone(config('app.timezone'))->format('Y-m-d\TH:i')
                : '',
            'tag_ids' => $survey->tags->pluck('id')->all(),
            'questions' => $questions,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{title: string, description: string, is_public: bool, privacy_mode: ?SurveyPrivacyMode, questions: array<int, array{text: string, type: string, options: array<int, string>}>, data_scadenza: ?CarbonInterface, tag_ids: array<int, int>, errors: array<string, string>}
     */
    private function validateSurveyPayload(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $isPublic = isset($input['is_public']);
        $questionsRaw = $input['questions'] ?? [];
        $errors = [];

        $privacyMode = SurveyPrivacyMode::tryFrom(trim((string) ($input['privacy_mode'] ?? '')));
        if ($privacyMode === null) {
            $errors['privacy_mode'] = 'Seleziona una modalità privacy valida.';
        }

        $dataScadenza = null;
        $rawScadenza = $input['data_scadenza'] ?? null;
        if ($rawScadenza !== null && trim((string) $rawScadenza) !== '') {
            try {
                $dataScadenza = Carbon::parse((string) $rawScadenza, config('app.timezone'));
            } catch (\Throwable) {
                $errors['data_scadenza'] = 'Data di scadenza non valida.';
            }
        }

        $tagIdsRaw = $input['tag_ids'] ?? [];
        if (! is_array($tagIdsRaw)) {
            $tagIdsRaw = [];
        }
        $tagIds = array_values(array_unique(array_filter(
            array_map('intval', $tagIdsRaw),
            fn (int $id): bool => $id > 0
        )));
        if ($tagIds !== [] && count($tagIds) !== Tag::query()->whereIn('id', $tagIds)->count()) {
            $errors['tags'] = 'Uno o più tag non sono validi.';
        }

        if ($title === '') {
            $errors['title'] = 'Titolo obbligatorio.';
        }

        $questions = [];
        if (! is_array($questionsRaw) || count($questionsRaw) === 0) {
            $errors['questions'] = 'Aggiungi almeno una domanda.';
        } else {
            foreach ($questionsRaw as $questionRaw) {
                if (! is_array($questionRaw)) {
                    continue;
                }
                $text = trim((string) ($questionRaw['text'] ?? ''));
                $type = (string) ($questionRaw['type'] ?? 'singola');
                $optionsRaw = $questionRaw['options'] ?? [];

                if ($text === '' || ! in_array($type, ['singola', 'multipla'], true)) {
                    continue;
                }

                $options = array_values(array_filter(array_map('trim', (array) $optionsRaw), fn ($v) => $v !== ''));
                if (count($options) < 2) {
                    $errors['questions'] = 'Ogni domanda deve avere almeno due opzioni.';

                    continue;
                }

                $questions[] = [
                    'text' => $text,
                    'type' => $type,
                    'options' => $options,
                ];
            }
        }

        if (count($questions) === 0 && ! isset($errors['questions'])) {
            $errors['questions'] = 'Formato domande non valido.';
        }

        return [
            'title' => $title,
            'description' => $description,
            'is_public' => $isPublic,
            'privacy_mode' => $privacyMode,
            'questions' => $questions,
            'data_scadenza' => $dataScadenza,
            'tag_ids' => $tagIds,
            'errors' => $errors,
        ];
    }
}
