<?php

namespace Tests\Feature;

use App\Models\Domanda;
use App\Models\Opzione;
use App\Models\Risposta;
use App\Models\Sondaggio;
use App\Models\User;
use App\Services\SurveyService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SurveyFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_guest_can_view_home(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_survey_store_rejects_invalid_privacy_mode_without_persisting(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);

        $response = $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'F8 privacy invalida',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'non_esiste',
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertSee('modalità privacy', false);
        $this->assertDatabaseMissing('sondaggi', ['titolo' => 'F8 privacy invalida']);
    }

    public function test_survey_create_persists_each_valid_privacy_mode(): void
    {
        foreach (['anonymous', 'identified_hidden_answers', 'identified_full'] as $mode) {
            $user = User::factory()->create(['password_hash' => Hash::make('password')]);
            $title = 'F8 mode '.$mode;

            $this->actingAs($user)
                ->post(route('surveys.store'), [
                    'title' => $title,
                    'description' => '',
                    'is_public' => '1',
                    'privacy_mode' => $mode,
                    'questions' => [
                        [
                            'text' => 'Q',
                            'type' => 'singola',
                            'options' => ['A', 'B'],
                        ],
                    ],
                ])
                ->assertRedirect(route('dashboard'));

            $survey = Sondaggio::query()->where('titolo', $title)->firstOrFail();
            $this->assertSame($mode, $survey->privacy_mode->value);
        }
    }

    public function test_user_can_create_survey_and_duplicate_public_submit_is_blocked(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password'),
        ]);

        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Test',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Domanda uno',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($user)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [
                    (string) $domanda->id => (string) $optA->id,
                ],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [
                    (string) $domanda->id => (string) $optA->id,
                ],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [
                    (string) $domanda->id => (string) $optA->id,
                ],
            ])
            ->assertOk();

        $this->assertDatabaseCount('risposte', 1);
    }

    public function test_anonymous_survey_saves_without_utente_id_cookie_and_blocks_second_submit(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password'),
        ]);

        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Anonimo submit F3',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'anonymous',
                'questions' => [
                    [
                        'text' => 'Una scelta',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Anonimo submit F3')->firstOrFail();
        $this->assertTrue($survey->isPrivacyAnonymous());
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $first = $this->actingAs($user)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [(string) $domanda->id => (string) $optA->id],
            ]);
        $first->assertOk();

        $r = Risposta::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $this->assertNull($r->utente_id);
        $this->assertNotNull($r->client_id);

        $cookieName = config('sondaggi.anonymous_vote_cookie');
        $first->assertCookie($cookieName, (string) $r->client_id);

        $this->actingAs($user)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [(string) $domanda->id => (string) $optA->id],
            ])
            ->assertOk()
            ->assertSee('Hai già inviato una risposta', false);

        $this->assertDatabaseCount('risposte', 1);
    }

    public function test_take_page_privacy_notice_inside_form_before_submit_for_each_privacy_mode(): void
    {
        $cases = [
            'anonymous' => [
                'privacy_mode' => 'anonymous',
                'needles' => ['survey-take-privacy-region', 'Sondaggio anonimo', 'non sono collegate al tuo account'],
            ],
            'identified_hidden_answers' => [
                'privacy_mode' => 'identified_hidden_answers',
                'needles' => ['survey-take-privacy-region', 'hai partecipato', 'quali opzioni'],
            ],
            'identified_full' => [
                'privacy_mode' => 'identified_full',
                'needles' => ['survey-take-privacy-region', 'partecipato', 'dettaglio delle risposte'],
            ],
        ];

        foreach ($cases as $label => $case) {
            $user = User::factory()->create(['password_hash' => Hash::make('password')]);
            $title = 'Take privacy '.$label;

            $this->actingAs($user)
                ->post(route('surveys.store'), [
                    'title' => $title,
                    'description' => '',
                    'is_public' => '1',
                    'privacy_mode' => $case['privacy_mode'],
                    'questions' => [
                        [
                            'text' => 'Q',
                            'type' => 'singola',
                            'options' => ['A', 'B'],
                        ],
                    ],
                ])
                ->assertRedirect(route('dashboard'));

            $survey = Sondaggio::query()->where('titolo', $title)->firstOrFail();
            $html = $this->actingAs($user)
                ->get(route('surveys.show', $survey->takeRouteParameters()))
                ->assertOk()
                ->getContent();

            foreach ($case['needles'] as $needle) {
                $this->assertStringContainsString($needle, $html, "Missing [{$needle}] for mode {$label}");
            }

            $this->assertTakePrivacyNoticeIsInsideFormBeforeSubmit($html, $label);
        }
    }

    private function assertTakePrivacyNoticeIsInsideFormBeforeSubmit(string $html, string $label): void
    {
        $dom = new \DOMDocument();
        $wrapped = '<?xml encoding="UTF-8">'.$html;
        @$dom->loadHTML($wrapped, LIBXML_NOERROR | LIBXML_NOWARNING);

        $form = $dom->getElementById('survey-take-form');
        $region = $dom->getElementById('survey-take-privacy-region');
        $this->assertNotNull($form, "Form #survey-take-form missing for {$label}");
        $this->assertNotNull($region, "Privacy region missing for {$label}");

        $el = $region;
        $insideForm = false;
        while ($el instanceof \DOMElement) {
            if ($el->getAttribute('id') === 'survey-take-form') {
                $insideForm = true;
                break;
            }
            $el = $el->parentNode;
        }
        $this->assertTrue($insideForm, "Privacy box must be inside #survey-take-form for {$label}");

        $xpath = new \DOMXPath($dom);
        $submitNodes = $xpath->query('//*[@id="survey-take-form"]//button[@type="submit"]');
        $this->assertSame(1, $submitNodes->length, "Single submit button expected for {$label}");
        $submit = $submitNodes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $submit);
        $flags = $region->compareDocumentPosition($submit);
        $this->assertNotSame(
            0,
            $flags & \DOMNode::DOCUMENT_POSITION_FOLLOWING,
            "Privacy box must appear before submit button for {$label}"
        );

        $questionNodes = $xpath->query('//*[@id="survey-take-form"]//fieldset[@data-question-id]');
        if ($questionNodes->length > 0) {
            $lastQuestion = $questionNodes->item($questionNodes->length - 1);
            $this->assertInstanceOf(\DOMElement::class, $lastQuestion);
            $afterLast = $lastQuestion->compareDocumentPosition($region);
            $this->assertNotSame(
                0,
                $afterLast & \DOMNode::DOCUMENT_POSITION_FOLLOWING,
                "Privacy box must appear after the last question for {$label}"
            );
        }
    }

    public function test_closed_take_page_includes_privacy_notice(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);
        $past = now()->subDay()->format('Y-m-d\TH:i');

        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Chiuso privacy F4',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'anonymous',
                'data_scadenza' => $past,
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Chiuso privacy F4')->firstOrFail();

        $html = $this->actingAs($user)
            ->get(route('surveys.show', $survey->takeRouteParameters()))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('survey-take-privacy-region', $html);
        $this->assertStringContainsString('Sondaggio anonimo', $html);
    }

    public function test_stats_count_only_responses_with_details(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);
        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Stats',
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

        $survey = Sondaggio::query()->where('titolo', 'Stats')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($user)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [(string) $domanda->id => (string) $optA->id],
            ])
            ->assertOk();

        $stats = app(SurveyService::class)->statsBySurvey($survey->id);
        $this->assertSame(1, $stats['total_responses']);
        $this->assertSame(1, (int) $stats['questions'][0]['options'][0]['votes']);
    }

    public function test_update_preserves_question_ids_when_responses_exist(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);
        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Preserve',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Q originale',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Preserve')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();
        $optIdBefore = (int) $optA->id;

        $this->actingAs($user)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [(string) $domanda->id => (string) $optA->id],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post(route('surveys.update', $survey), [
                'title' => 'Preserve',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Q aggiornata',
                        'type' => 'singola',
                        'options' => ['A2', 'B2'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($optIdBefore, (int) Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail()->id);
        $stats = app(SurveyService::class)->statsBySurvey($survey->id);
        $this->assertSame(1, $stats['total_responses']);
        $this->assertSame(1, (int) $stats['questions'][0]['options'][0]['votes']);
    }

    public function test_update_rejects_structure_change_when_responses_exist(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);
        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Struct',
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

        $survey = Sondaggio::query()->where('titolo', 'Struct')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($user)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [(string) $domanda->id => (string) $optA->id],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->from(route('surveys.edit', $survey))
            ->post(route('surveys.update', $survey), [
                'title' => 'Struct',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Q1',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                    [
                        'text' => 'Q2',
                        'type' => 'singola',
                        'options' => ['C', 'D'],
                    ],
                ],
            ])
            ->assertRedirect(route('surveys.edit', $survey))
            ->assertSessionHasErrors(['questions']);
    }

    public function test_guest_is_redirected_to_login_when_visiting_public_survey_show(): void
    {
        $author = User::factory()->create();
        $survey = Sondaggio::query()->create([
            'titolo' => 'Pubblico guest',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => true,
        ]);

        $this->get(route('surveys.show', $survey->takeRouteParameters()))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_visiting_private_survey_show(): void
    {
        $author = User::factory()->create();
        $survey = Sondaggio::query()->create([
            'titolo' => 'Privato guest',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => false,
        ]);

        $this->get(route('surveys.show', $survey->takeRouteParameters()))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_posting_survey_submit(): void
    {
        $author = User::factory()->create();
        $survey = Sondaggio::query()->create([
            'titolo' => 'Submit guest',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => true,
        ]);

        $this->post(route('surveys.submit', $survey->takeRouteParameters()), [
            'answers' => [],
        ])->assertRedirect(route('login'));
    }

    public function test_expired_survey_submit_does_not_persist_response_and_shows_error(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password'),
        ]);

        $past = now()->subDays(2)->format('Y-m-d\TH:i');

        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Scaduto test',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'data_scadenza' => $past,
                'questions' => [
                    [
                        'text' => 'Unica domanda',
                        'type' => 'singola',
                        'options' => ['Sì', 'No'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Scaduto test')->firstOrFail();
        $this->assertTrue($survey->fresh()->isScaduto());

        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $response = $this->actingAs($user)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [(string) $domanda->id => (string) $optA->id],
            ]);

        $response->assertOk();
        $response->assertSee('non accetta più risposte', false);
        $this->assertDatabaseCount('risposte', 0);
    }

    public function test_expired_survey_take_page_shows_closed_state_for_authenticated_user(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $past = now()->subDay()->format('Y-m-d\TH:i');

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Scaduto vista',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'data_scadenza' => $past,
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Scaduto vista')->firstOrFail();
        $this->assertTrue($survey->isScaduto());

        $response = $this->actingAs($author)->get(route('surveys.show', $survey->takeRouteParameters()));

        $response->assertOk();
        $response->assertSee('Sondaggio chiuso', false);
        $response->assertSee('Non è più possibile inviare risposte', false);
        $response->assertDontSee('id="survey-take-form"', false);
        $response->assertSee('Torna alla home', false);
    }

    public function test_public_surveys_index_excludes_expired_surveys(): void
    {
        $author = User::factory()->create();

        Sondaggio::query()->create([
            'titolo' => 'Lista pub ZZZ scaduto',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => true,
            'data_scadenza' => now()->subDay(),
        ]);

        Sondaggio::query()->create([
            'titolo' => 'Lista pub AAA attivo',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => true,
            'data_scadenza' => now()->addWeek(),
        ]);

        $html = $this->get(route('surveys.public.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Lista pub ZZZ scaduto', $html);
        $this->assertStringContainsString('Lista pub AAA attivo', $html);
    }

    public function test_home_excludes_expired_public_surveys(): void
    {
        $author = User::factory()->create();

        Sondaggio::query()->create([
            'titolo' => 'Home hide ZZZ scaduto',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => true,
            'data_scadenza' => now()->subDay(),
        ]);

        Sondaggio::query()->create([
            'titolo' => 'Home show AAA attivo',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => true,
            'data_scadenza' => now()->addWeek(),
        ]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('Home hide ZZZ scaduto', $html);
        $this->assertStringContainsString('Home show AAA attivo', $html);
    }

    public function test_expired_public_survey_excluded_from_index_but_accessible_via_direct_url(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);
        $author = User::factory()->create();

        $expired = Sondaggio::query()->create([
            'titolo' => 'Solo URL non in lista',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => true,
            'data_scadenza' => now()->subDay(),
        ]);
        $this->assertTrue($expired->fresh()->isScaduto());

        $this->assertStringNotContainsString(
            'Solo URL non in lista',
            $this->get(route('surveys.public.index'))->assertOk()->getContent()
        );

        $response = $this->actingAs($user)->get(route('surveys.show', $expired->takeRouteParameters()));
        $response->assertOk();
        $response->assertSee('Sondaggio chiuso', false);
        $response->assertDontSee('id="survey-take-form"', false);
    }

    public function test_is_scaduto_boundary_matches_strict_greater_than(): void
    {
        $frozen = Carbon::parse('2030-06-15 14:30:00', config('app.timezone'));
        Carbon::setTestNow($frozen);

        $author = User::factory()->create();
        $survey = Sondaggio::query()->create([
            'titolo' => 'Bordo scadenza',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => true,
            'data_scadenza' => $frozen->copy(),
        ]);

        $this->assertFalse($survey->fresh()->isScaduto());

        Carbon::setTestNow($frozen->copy()->addSecond());
        $this->assertTrue($survey->fresh()->isScaduto());

        Carbon::setTestNow();
    }

    public function test_dashboard_expired_survey_shows_badge_and_hides_edit_link(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);
        $past = now()->subDay()->format('Y-m-d\TH:i');

        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Dash scaduto F4',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'data_scadenza' => $past,
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Dash scaduto F4')->firstOrFail();
        $this->assertTrue($survey->isScaduto());

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Scaduto', false);
        $this->assertStringNotContainsString((string) route('surveys.edit', $survey), $response->getContent());
    }

    public function test_expired_survey_edit_and_update_return_403(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);
        $past = now()->subDay()->format('Y-m-d\TH:i');

        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Edit bloccato F4',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'data_scadenza' => $past,
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Edit bloccato F4')->firstOrFail();
        $this->assertTrue($survey->isScaduto());

        $this->actingAs($user)
            ->get(route('surveys.edit', $survey))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('surveys.update', $survey), [
                'title' => 'Edit bloccato F4',
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
            ->assertForbidden();
    }

    public function test_stats_data_endpoint_returns_json_for_author_and_forbidden_for_other(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $other = User::factory()->create(['password_hash' => Hash::make('password')]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Json stats endpoint',
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

        $survey = Sondaggio::query()->where('titolo', 'Json stats endpoint')->firstOrFail();

        $authorResponse = $this->actingAs($author)
            ->getJson(route('surveys.stats.data', $survey));

        $authorResponse->assertOk();
        $this->assertStringContainsString('application/json', (string) $authorResponse->headers->get('Content-Type'));
        $authorResponse->assertJsonStructure([
            'total_responses',
            'questions' => [
                '*' => [
                    'id',
                    'testo',
                    'tipo',
                    'options' => [
                        '*' => ['id', 'testo', 'votes', 'percentuale'],
                    ],
                ],
            ],
        ]);

        $this->actingAs($other)
            ->getJson(route('surveys.stats.data', $survey))
            ->assertForbidden();
    }

    public function test_stats_data_json_never_includes_responder_email_or_participants_key(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $responder = User::factory()->create([
            'password_hash' => Hash::make('password'),
            'email' => 'stats-json-pii-check@example.test',
        ]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Stats JSON no PII',
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

        $survey = Sondaggio::query()->where('titolo', 'Stats JSON no PII')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($responder)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [
                    (string) $domanda->id => (string) $optA->id,
                ],
            ])
            ->assertOk();

        $payload = $this->actingAs($author)
            ->getJson(route('surveys.stats.data', $survey))
            ->assertOk()
            ->json();

        $this->assertArrayNotHasKey('participants', $payload);
        $this->assertArrayNotHasKey('participant_insights', $payload);
        $this->assertStringNotContainsString(
            'stats-json-pii-check@example.test',
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    public function test_stats_page_includes_participant_insights_mode_marker(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);

        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Stats marker anon',
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

        $survey = Sondaggio::query()->where('titolo', 'Stats marker anon')->firstOrFail();

        $html = $this->actingAs($user)
            ->get(route('surveys.stats', $survey))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="stats-participants-section"', $html);
        $this->assertStringContainsString('data-participant-mode="anonymous"', $html);
        $this->assertStringContainsString('Privacy del sondaggio', $html);
    }

    public function test_stats_page_identified_full_shows_participant_row_with_answer_detail(): void
    {
        $author = User::factory()->create([
            'password_hash' => Hash::make('password'),
            'nome' => 'AutoreStatsF6',
        ]);
        $responder = User::factory()->create([
            'password_hash' => Hash::make('password'),
            'nome' => 'RispStatsF6',
            'email' => 'risp-stats-f6@example.test',
        ]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Stats F6 full',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Domanda visibile F6',
                        'type' => 'singola',
                        'options' => ['SceltaF6Alfa', 'SceltaF6Beta'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Stats F6 full')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($responder)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [
                    (string) $domanda->id => (string) $optA->id,
                ],
            ])
            ->assertOk();

        $html = $this->actingAs($author)
            ->get(route('surveys.stats', $survey))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="stats-participants-section"', $html);
        $this->assertStringContainsString('data-participant-mode="identified_full"', $html);
        $this->assertStringContainsString('RispStatsF6', $html);
        $this->assertStringContainsString('risp-stats-f6@example.test', $html);
        $this->assertStringContainsString('Domanda visibile F6', $html);
        $this->assertStringContainsString('SceltaF6Alfa', $html);
        $this->assertStringContainsString('data-sm-participants-search-input', $html);
    }

    public function test_stats_page_identified_hidden_has_no_answers_column_in_participants_table(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $responder = User::factory()->create([
            'password_hash' => Hash::make('password'),
            'nome' => 'NascostoF6',
            'email' => 'nascosto-f6@example.test',
        ]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Stats F6 hidden',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_hidden_answers',
                'questions' => [
                    [
                        'text' => 'Q nascosta',
                        'type' => 'singola',
                        'options' => ['EtichettaNascostaF6', 'AltroF6'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Stats F6 hidden')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($responder)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [
                    (string) $domanda->id => (string) $optA->id,
                ],
            ])
            ->assertOk();

        $html = $this->actingAs($author)
            ->get(route('surveys.stats', $survey))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-participant-mode="identified_hidden_answers"', $html);
        $this->assertStringContainsString('NascostoF6', $html);
        $this->assertStringNotContainsString('>Risposte</th>', $html);
    }

    public function test_stats_report_pdf_for_author_returns_pdf_and_forbidden_for_other(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $other = User::factory()->create(['password_hash' => Hash::make('password')]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Pdf report endpoint',
                'description' => 'Descrizione di prova',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Q1',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Pdf report endpoint')->firstOrFail();

        $authorResponse = $this->actingAs($author)->get(route('surveys.stats.report', $survey));
        $authorResponse->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $authorResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $authorResponse->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $authorResponse->getContent());

        $this->actingAs($other)
            ->get(route('surveys.stats.report', $survey))
            ->assertForbidden();
    }

    public function test_stats_data_and_report_forbidden_for_same_non_author(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $other = User::factory()->create(['password_hash' => Hash::make('password')]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Stats auth entrambi F5',
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

        $survey = Sondaggio::query()->where('titolo', 'Stats auth entrambi F5')->firstOrFail();

        $this->actingAs($other)
            ->getJson(route('surveys.stats.data', $survey))
            ->assertForbidden();
        $this->actingAs($other)
            ->get(route('surveys.stats.report', $survey))
            ->assertForbidden();
    }

    public function test_expired_survey_stats_has_no_share_invitation(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);
        $past = now()->subDay()->format('Y-m-d\TH:i');

        $this->actingAs($user)
            ->post(route('surveys.store'), [
                'title' => 'Stats chiuso F4',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'data_scadenza' => $past,
                'questions' => [
                    [
                        'text' => 'Q',
                        'type' => 'singola',
                        'options' => ['A', 'B'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Stats chiuso F4')->firstOrFail();

        $response = $this->actingAs($user)->get(route('surveys.stats', $survey));
        $response->assertOk();
        $response->assertSee('Sondaggio chiuso', false);
        $html = $response->getContent();
        $this->assertStringNotContainsString('data-sm-stats-copy-link', $html);
        $this->assertStringNotContainsString('Condividi link', $html);
    }

    public function test_dashboard_orders_non_expired_before_expired_despite_higher_id_on_expired(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('password')]);

        $active = Sondaggio::query()->create([
            'titolo' => 'Dash ordine AAA attivo',
            'descrizione' => null,
            'autore_id' => $user->id,
            'is_pubblico' => true,
            'data_scadenza' => now()->addWeek(),
        ]);

        $expired = Sondaggio::query()->create([
            'titolo' => 'Dash ordine ZZZ scaduto',
            'descrizione' => null,
            'autore_id' => $user->id,
            'is_pubblico' => true,
            'data_scadenza' => now()->subDay(),
        ]);

        $this->assertGreaterThan((int) $active->id, (int) $expired->id, 'Lo scaduto deve avere id maggiore dell’attivo per simulare ordinamento sfavorevole.');

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
        $posActive = mb_strpos($html, 'Dash ordine AAA attivo');
        $posExpired = mb_strpos($html, 'Dash ordine ZZZ scaduto');
        $this->assertNotFalse($posActive);
        $this->assertNotFalse($posExpired);
        $this->assertLessThan($posExpired, $posActive);
    }

    public function test_stats_pdf_view_anonymous_excludes_submitter_email_from_participant_style_section(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $responder = User::factory()->create([
            'password_hash' => Hash::make('password'),
            'email' => 'pdf-f7-anon-responder@example.test',
        ]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Pdf F7 anonymous privacy',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'anonymous',
                'questions' => [
                    [
                        'text' => 'Q pdf anon',
                        'type' => 'singola',
                        'options' => ['Uno', 'Due'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Pdf F7 anonymous privacy')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($responder)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [
                    (string) $domanda->id => (string) $optA->id,
                ],
            ])
            ->assertOk();

        $html = $this->renderStatsPdfHtml($survey->fresh());

        $this->assertStringNotContainsString('pdf-f7-anon-responder@example.test', $html);
        $this->assertStringNotContainsString('Partecipanti</p>', $html);
    }

    public function test_stats_pdf_view_identified_full_includes_answer_text_in_participant_blocks(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $responder = User::factory()->create(['password_hash' => Hash::make('password')]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Pdf F7 full',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_full',
                'questions' => [
                    [
                        'text' => 'Domanda report PDF F7',
                        'type' => 'singola',
                        'options' => ['EtichettaPdfF7Full', 'AltraF7'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Pdf F7 full')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($responder)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [
                    (string) $domanda->id => (string) $optA->id,
                ],
            ])
            ->assertOk();

        $html = $this->renderStatsPdfHtml($survey->fresh());

        $this->assertStringContainsString('Partecipanti</p>', $html);
        $this->assertStringContainsString('Domanda report PDF F7', $html);
        $this->assertStringContainsString('EtichettaPdfF7Full', $html);
    }

    public function test_stats_pdf_view_identified_hidden_lists_participants_without_qa_table_header(): void
    {
        $author = User::factory()->create(['password_hash' => Hash::make('password')]);
        $responder = User::factory()->create([
            'password_hash' => Hash::make('password'),
            'nome' => 'NomePdfF7Hidden',
            'email' => 'pdf-f7-hidden@example.test',
        ]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Pdf F7 hidden',
                'description' => '',
                'is_public' => '1',
                'privacy_mode' => 'identified_hidden_answers',
                'questions' => [
                    [
                        'text' => 'Q hidden pdf',
                        'type' => 'singola',
                        'options' => ['H1', 'H2'],
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $survey = Sondaggio::query()->where('titolo', 'Pdf F7 hidden')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        $this->actingAs($responder)
            ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                'answers' => [
                    (string) $domanda->id => (string) $optA->id,
                ],
            ])
            ->assertOk();

        $html = $this->renderStatsPdfHtml($survey->fresh());

        $this->assertStringContainsString('pdf-f7-hidden@example.test', $html);
        $this->assertStringContainsString('NomePdfF7Hidden', $html);
        $this->assertStringNotContainsString('<th style="width: 38%;">Domanda</th>', $html);
    }

    public function test_stats_pdf_view_shows_truncation_notice_when_limit_exceeded(): void
    {
        Config::set('sondaggi.stats_pdf_max_participants', 2);

        $author = User::factory()->create(['password_hash' => Hash::make('password')]);

        $this->actingAs($author)
            ->post(route('surveys.store'), [
                'title' => 'Pdf F7 truncate',
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

        $survey = Sondaggio::query()->where('titolo', 'Pdf F7 truncate')->firstOrFail();
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optA = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->firstOrFail();

        for ($i = 0; $i < 3; $i++) {
            $u = User::factory()->create(['password_hash' => Hash::make('password')]);
            $this->actingAs($u)
                ->post(route('surveys.submit', $survey->takeRouteParameters()), [
                    'answers' => [
                        (string) $domanda->id => (string) $optA->id,
                    ],
                ])
                ->assertOk();
        }

        $html = $this->renderStatsPdfHtml($survey->fresh());

        $this->assertStringContainsString('Nel report sono inclusi i primi', $html);
        $this->assertStringContainsString('<strong>2</strong>', $html);
        $this->assertStringContainsString('<strong>3</strong>', $html);
    }

    private function renderStatsPdfHtml(Sondaggio $sondaggio): string
    {
        $svc = app(SurveyService::class);
        $sondaggio->loadMissing('autore');
        $survey = $svc->loadWithQuestions($sondaggio);
        $maxPdf = (int) config('sondaggi.stats_pdf_max_participants', 500);
        $limit = $maxPdf > 0 ? $maxPdf : null;

        return view('surveys.reports.stats-pdf', [
            'survey' => $survey,
            'stats' => $svc->statsBySurvey($sondaggio->id),
            'is_scaduto' => $sondaggio->isScaduto(),
            'generated_at' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'author' => $sondaggio->autore,
            'participant_insights' => $svc->participantInsightsForCreator($sondaggio, null, $limit),
        ])->render();
    }
}
