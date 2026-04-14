<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Domanda;
use App\Models\Opzione;
use App\Models\Risposta;
use App\Models\Sondaggio;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicSurveyParticipationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_public_index_lists_identified_surveys_not_yet_answered_before_answered_ones(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $voter = User::factory()->create(['password_hash' => Hash::make('password')]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Pub Partec Alpha',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Pub Partec Beta',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $beta = Sondaggio::query()->where('titolo', 'Pub Partec Beta')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $beta->id)->firstOrFail();
        $opt = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($voter)
            ->post(route('surveys.submit', $beta->takeRouteParameters()), [
                'answers' => [(string) $domanda->id => (string) $opt->id],
            ])
            ->assertOk();

        $html = $this->actingAs($voter)->get(route('surveys.public.index'))->assertOk()->getContent();
        $pAlpha = strpos($html, 'Pub Partec Alpha');
        $pBeta = strpos($html, 'Pub Partec Beta');
        $this->assertNotFalse($pAlpha);
        $this->assertNotFalse($pBeta);
        $this->assertLessThan($pBeta, $pAlpha, 'Il sondaggio non ancora compilato deve precedere quello già risposto.');
        $this->assertStringContainsString('site-public-card--answered', $html);
    }

    public function test_public_search_json_keeps_answered_surveys_last(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $voter = User::factory()->create(['password_hash' => Hash::make('password')]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Ricerca Partec Uno',
                'description' => 'xyzpartec',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Ricerca Partec Due',
                'description' => 'xyzpartec',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $due = Sondaggio::query()->where('titolo', 'Ricerca Partec Due')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $due->id)->firstOrFail();
        $opt = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($voter)
            ->post(route('surveys.submit', $due->takeRouteParameters()), [
                'answers' => [(string) $domanda->id => (string) $opt->id],
            ])
            ->assertOk();

        $response = $this->actingAs($voter)->getJson('/sondaggi/ricerca?q='.urlencode('xyzpartec'));
        $response->assertOk();
        $html = $response->json('cards_html');
        $pUno = strpos($html, 'Ricerca Partec Uno');
        $pDue = strpos($html, 'Ricerca Partec Due');
        $this->assertNotFalse($pUno);
        $this->assertNotFalse($pDue);
        $this->assertLessThan($pDue, $pUno);
    }

    public function test_anonymous_public_survey_shows_answered_when_cookie_matches(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $voter = User::factory()->create(['password_hash' => Hash::make('password')]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Anon Pub Solo',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'anonymous',
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Anon Pub Solo')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $opt = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $submit = $this->actingAs($voter)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [(string) $domanda->id => (string) $opt->id],
            ]);
        $submit->assertOk();

        $cookieName = (string) config('sondaggi.anonymous_vote_cookie');
        $clientId = (string) Risposta::query()->where('sondaggio_id', $survey->id)->value('client_id');

        $html = $this->actingAs($voter)
            ->withCookie($cookieName, $clientId)
            ->get(route('surveys.public.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('site-public-card--answered', $html);
    }
}
