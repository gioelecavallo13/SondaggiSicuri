<?php

namespace Tests\Feature;

use App\Models\Sondaggio;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class PublicSurveySearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_ricerca_json_returns_matching_survey_by_title(): void
    {
        $user = User::factory()->create();
        Sondaggio::query()->create([
            'titolo' => 'Titolo Unico Ricerca XYZ',
            'descrizione' => null,
            'autore_id' => $user->id,
            'is_pubblico' => true,
        ]);

        $response = $this->getJson('/sondaggi/ricerca?q='.urlencode('Unico Ricerca'));

        $response->assertOk();
        $response->assertJsonStructure(['cards_html', 'pagination_html', 'empty']);
        $this->assertFalse($response->json('empty'));
        $this->assertStringContainsString('Titolo Unico Ricerca XYZ', $response->json('cards_html'));
    }

    public function test_ricerca_json_filters_by_tag_ids(): void
    {
        $user = User::factory()->create();
        $survey = Sondaggio::query()->create([
            'titolo' => 'Sondaggio con Sport',
            'descrizione' => 'Descrizione',
            'autore_id' => $user->id,
            'is_pubblico' => true,
        ]);
        Sondaggio::query()->create([
            'titolo' => 'Altro senza tag',
            'descrizione' => null,
            'autore_id' => $user->id,
            'is_pubblico' => true,
        ]);

        $sportTag = Tag::query()->where('slug', 'sport')->firstOrFail();
        $survey->tags()->attach($sportTag->id);

        $response = $this->getJson('/sondaggi/ricerca?tags[]='.$sportTag->id);

        $response->assertOk();
        $html = $response->json('cards_html');
        $this->assertStringContainsString('Sondaggio con Sport', $html);
        $this->assertStringNotContainsString('Altro senza tag', $html);
    }

    public function test_ricerca_json_combines_query_and_tags(): void
    {
        $user = User::factory()->create();
        $cinema = Tag::query()->where('slug', 'cinema')->firstOrFail();

        $a = Sondaggio::query()->create([
            'titolo' => 'Film del mese',
            'descrizione' => null,
            'autore_id' => $user->id,
            'is_pubblico' => true,
        ]);
        $a->tags()->attach($cinema->id);

        Sondaggio::query()->create([
            'titolo' => 'Film senza tag',
            'descrizione' => null,
            'autore_id' => $user->id,
            'is_pubblico' => true,
        ]);

        $response = $this->getJson('/sondaggi/ricerca?q='.urlencode('Film').'&tags[]='.$cinema->id);

        $response->assertOk();
        $html = $response->json('cards_html');
        $this->assertStringContainsString('Film del mese', $html);
        $this->assertStringNotContainsString('Film senza tag', $html);
    }

    public function test_ricerca_json_excludes_expired_public_surveys(): void
    {
        $user = User::factory()->create();
        Sondaggio::query()->create([
            'titolo' => 'Scaduto Ricerca ABC',
            'descrizione' => null,
            'autore_id' => $user->id,
            'is_pubblico' => true,
            'data_scadenza' => now()->subDay(),
        ]);

        $response = $this->getJson('/sondaggi/ricerca?q='.urlencode('Ricerca ABC'));

        $response->assertOk();
        $this->assertStringNotContainsString('Scaduto Ricerca ABC', $response->json('cards_html'));
    }
}
