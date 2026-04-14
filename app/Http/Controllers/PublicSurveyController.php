<?php

namespace App\Http\Controllers;

use App\Models\Sondaggio;
use App\Models\Tag;
use App\Services\ResponseSubmissionService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class PublicSurveyController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(
        private readonly ResponseSubmissionService $responseSubmission,
    ) {}

    public function index(Request $request): View
    {
        $paginator = $this->paginatedPublicSurveys($request);
        $allTags = Tag::query()->orderBy('nome')->get(['id', 'nome', 'slug']);

        return view('surveys.public-index', [
            'surveys' => $paginator,
            'allTags' => $allTags,
            'searchQuery' => mb_substr(trim((string) $request->input('q', '')), 0, 255),
            'selectedTagIds' => $this->validatedTagIds($request),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'integer',
            'page' => 'nullable|integer|min:1',
        ]);

        $paginator = $this->paginatedPublicSurveys($request);

        return response()->json([
            'cards_html' => view('surveys.partials.public-survey-cards', [
                'surveys' => $paginator->getCollection(),
            ])->render(),
            'empty' => $paginator->isEmpty(),
            'pagination_html' => view('surveys.partials.public-survey-pagination', [
                'paginator' => $paginator,
            ])->render(),
        ]);
    }

    private function paginatedPublicSurveys(Request $request): LengthAwarePaginator
    {
        $paginator = $this->filteredBuilder($request)
            ->paginate(self::PER_PAGE)
            ->withQueryString();
        $paginator->setPath(route('surveys.public.index'));
        $this->annotateViewerParticipation($paginator, $request);

        return $paginator;
    }

    private function annotateViewerParticipation(LengthAwarePaginator $paginator, Request $request): void
    {
        $collection = $paginator->getCollection();
        if ($collection->isEmpty()) {
            return;
        }

        $ids = $collection->pluck('id')->map(fn ($id) => (int) $id)->all();
        $participated = $this->responseSubmission->participatedSurveyIdsForRequest($request, $ids);
        $flags = array_fill_keys($participated, true);
        $collection = $collection->map(function (Sondaggio $s) use ($flags) {
            $s->setAttribute('viewer_has_responded', isset($flags[$s->id]));

            return $s;
        });
        $paginator->setCollection($collection);
    }

    /**
     * @return Builder<Sondaggio>
     */
    private function filteredBuilder(Request $request): Builder
    {
        $builder = Sondaggio::query()
            ->pubblici()
            ->nonScaduti()
            ->with(['tags', 'autore'])
            ->withCount('risposte');

        $term = trim((string) $request->input('q', ''));
        if ($term !== '') {
            $term = mb_substr($term, 0, 255);
            $like = '%'.addcslashes($term, '%_\\').'%';
            $builder->where(function (Builder $q) use ($like) {
                $q->where('titolo', 'like', $like)
                    ->orWhere('descrizione', 'like', $like);
            });
        }

        $tagIds = $this->validatedTagIds($request);
        if ($tagIds !== []) {
            $builder->whereHas('tags', fn (Builder $q) => $q->whereIn('tags.id', $tagIds));
        }

        $this->responseSubmission->applyPublicSurveyListParticipationOrdering($builder, $request);

        return $builder;
    }

    /**
     * @return array<int, int>
     */
    private function validatedTagIds(Request $request): array
    {
        $raw = $request->input('tags', []);
        if (! is_array($raw)) {
            return [];
        }
        $ids = array_values(array_unique(array_map('intval', $raw)));
        $ids = array_values(array_filter($ids, fn (int $id) => $id > 0));
        if ($ids === []) {
            return [];
        }

        return Tag::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }
}
