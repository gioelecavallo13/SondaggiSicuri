<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\SurveyPrivacyMode;
use App\Models\DettaglioRisposta;
use App\Models\Domanda;
use App\Models\Opzione;
use App\Models\Risposta;
use App\Models\Sondaggio;
use App\Models\User;
use App\Services\SurveyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantInsightsForCreatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_mode_returns_empty_participants_and_explanation(): void
    {
        $author = User::factory()->create();
        $survey = Sondaggio::query()->create([
            'titolo' => 'S',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => true,
            'privacy_mode' => SurveyPrivacyMode::Anonymous,
            'data_scadenza' => null,
        ]);

        $out = app(SurveyService::class)->participantInsightsForCreator($survey->fresh());

        $this->assertSame('anonymous', $out['mode']);
        $this->assertFalse($out['shows_participant_list']);
        $this->assertFalse($out['shows_individual_answers']);
        $this->assertNotNull($out['anonymous_explanation']);
        $this->assertSame(0, $out['participant_count']);
        $this->assertSame([], $out['participants']);
        $this->assertSame(0, $out['participants_grand_total']);
        $this->assertFalse($out['participants_truncated']);
    }

    public function test_identified_hidden_has_participants_without_answers(): void
    {
        [$survey, $responder] = $this->seedSurveyWithOneResponse(SurveyPrivacyMode::IdentifiedHiddenAnswers);

        $out = app(SurveyService::class)->participantInsightsForCreator($survey);

        $this->assertSame('identified_hidden_answers', $out['mode']);
        $this->assertTrue($out['shows_participant_list']);
        $this->assertFalse($out['shows_individual_answers']);
        $this->assertNull($out['anonymous_explanation']);
        $this->assertSame(1, $out['participant_count']);
        $this->assertSame((int) $responder->id, $out['participants'][0]['user']['id']);
        $this->assertNull($out['participants'][0]['answers']);
        $this->assertSame(1, $out['participants_grand_total']);
        $this->assertFalse($out['participants_truncated']);
    }

    public function test_identified_full_includes_answers_per_question(): void
    {
        [$survey, , $opt] = $this->seedSurveyWithOneResponse(SurveyPrivacyMode::IdentifiedFull);

        $out = app(SurveyService::class)->participantInsightsForCreator($survey);

        $this->assertSame('identified_full', $out['mode']);
        $this->assertTrue($out['shows_individual_answers']);
        $answers = $out['participants'][0]['answers'];
        $this->assertIsArray($answers);
        $this->assertCount(1, $answers);
        $this->assertSame('Q1', $answers[0]['domanda_testo']);
        $this->assertSame($opt->testo, $answers[0]['opzioni'][0]['testo']);
        $this->assertSame(1, $out['participants_grand_total']);
        $this->assertFalse($out['participants_truncated']);
    }

    public function test_search_query_filters_by_email(): void
    {
        [$survey, $r1, $o1] = $this->seedSurveyWithOneResponse(SurveyPrivacyMode::IdentifiedFull, 'alpha@example.test');
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optB = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->skip(1)->firstOrFail();

        $r2 = User::factory()->create(['email' => 'beta@example.test']);
        $resp2 = Risposta::query()->create([
            'utente_id' => $r2->id,
            'sondaggio_id' => $survey->id,
            'client_id' => null,
            'session_fingerprint' => null,
            'ip_hash' => null,
        ]);
        DettaglioRisposta::query()->create([
            'risposta_id' => $resp2->id,
            'domanda_id' => $domanda->id,
            'opzione_id' => $optB->id,
        ]);

        $svc = app(SurveyService::class);
        $filtered = $svc->participantInsightsForCreator($survey->fresh(), 'beta@');

        $this->assertSame(1, $filtered['participant_count']);
        $this->assertSame((int) $r2->id, $filtered['participants'][0]['user']['id']);
        $this->assertSame(2, $filtered['participants_grand_total']);
        $this->assertFalse($filtered['participants_truncated']);
    }

    public function test_participant_limit_truncates_and_sets_truncated_flag(): void
    {
        $tuple = $this->seedSurveyWithOneResponse(SurveyPrivacyMode::IdentifiedFull);
        $survey = $tuple[0];
        $domanda = Domanda::query()->where('sondaggio_id', $survey->id)->firstOrFail();
        $optB = Opzione::query()->where('domanda_id', $domanda->id)->orderBy('ordine')->skip(1)->firstOrFail();

        for ($i = 0; $i < 2; $i++) {
            $u = User::factory()->create();
            $resp = Risposta::query()->create([
                'utente_id' => $u->id,
                'sondaggio_id' => $survey->id,
                'client_id' => null,
                'session_fingerprint' => null,
                'ip_hash' => null,
            ]);
            DettaglioRisposta::query()->create([
                'risposta_id' => $resp->id,
                'domanda_id' => $domanda->id,
                'opzione_id' => $optB->id,
            ]);
        }

        $out = app(SurveyService::class)->participantInsightsForCreator($survey->fresh(), null, 2);

        $this->assertSame(3, $out['participants_grand_total']);
        $this->assertTrue($out['participants_truncated']);
        $this->assertSame(2, $out['participant_count']);
        $this->assertCount(2, $out['participants']);
    }

    /**
     * @return array{0: Sondaggio, 1: User, 2: Opzione}
     */
    private function seedSurveyWithOneResponse(SurveyPrivacyMode $mode, ?string $responderEmail = null): array
    {
        $author = User::factory()->create();
        $survey = Sondaggio::query()->create([
            'titolo' => 'T',
            'descrizione' => null,
            'autore_id' => $author->id,
            'is_pubblico' => true,
            'privacy_mode' => $mode,
            'data_scadenza' => null,
        ]);

        $domanda = Domanda::query()->create([
            'sondaggio_id' => $survey->id,
            'testo' => 'Q1',
            'tipo' => 'singola',
            'ordine' => 1,
        ]);
        $optA = Opzione::query()->create([
            'domanda_id' => $domanda->id,
            'testo' => 'A',
            'ordine' => 1,
        ]);
        Opzione::query()->create([
            'domanda_id' => $domanda->id,
            'testo' => 'B',
            'ordine' => 2,
        ]);

        $responder = User::factory()->create(
            $responderEmail !== null ? ['email' => $responderEmail] : []
        );

        $risposta = Risposta::query()->create([
            'utente_id' => $responder->id,
            'sondaggio_id' => $survey->id,
            'client_id' => null,
            'session_fingerprint' => null,
            'ip_hash' => null,
        ]);
        DettaglioRisposta::query()->create([
            'risposta_id' => $risposta->id,
            'domanda_id' => $domanda->id,
            'opzione_id' => $optA->id,
        ]);

        return [$survey->fresh(['domande']), $responder, $optA];
    }
}
