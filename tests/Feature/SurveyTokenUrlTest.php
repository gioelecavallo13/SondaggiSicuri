<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Sondaggio;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SurveyTokenUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /** Vecchi bookmark con id numerico non corrispondono al vincolo sul token (48 caratteri). */
    public function test_legacy_numeric_sondaggi_path_returns_not_found(): void
    {
        $this->get('/sondaggi/123')->assertNotFound();
    }

    public function test_sondaggio_path_with_wrong_token_length_returns_not_found(): void
    {
        $this->get('/sondaggi/'.str_repeat('x', 47))->assertNotFound();
    }

    public function test_sondaggio_create_assigns_48_char_alphanumeric_access_token(): void
    {
        $user = User::factory()->create();
        $survey = Sondaggio::query()->create([
            'titolo' => 'Token Fase 6',
            'descrizione' => null,
            'autore_id' => $user->id,
            'is_pubblico' => true,
        ]);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{48}$/', (string) $survey->fresh()->access_token);
    }

    public function test_login_with_valid_take_redirect_goes_to_survey_after_success(): void
    {
        $user = User::factory()->create([
            'email' => 'take-redirect@example.test',
            'password_hash' => Hash::make('password123'),
        ]);
        $survey = Sondaggio::query()->create([
            'titolo' => 'Redirect post login',
            'descrizione' => null,
            'autore_id' => $user->id,
            'is_pubblico' => true,
        ]);
        $takeUrl = '/sondaggi/'.$survey->fresh()->access_token;

        $this->post(route('login'), [
            'email' => 'take-redirect@example.test',
            'password' => 'password123',
            'redirect' => $takeUrl,
        ])->assertRedirect($takeUrl);
    }

    public function test_login_with_legacy_numeric_redirect_falls_back_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy-redirect@example.test',
            'password_hash' => Hash::make('password123'),
        ]);

        $this->post(route('login'), [
            'email' => 'legacy-redirect@example.test',
            'password' => 'password123',
            'redirect' => '/sondaggi/99',
        ])->assertRedirect(route('dashboard'));
    }
}
