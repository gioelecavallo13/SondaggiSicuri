<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\SafeRedirect;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeRedirectTest extends TestCase
{
    public static function allowedTakePathsProvider(): array
    {
        $token = str_repeat('a', 48);

        return [
            'token no trailing slash' => ["/sondaggi/{$token}", "/sondaggi/{$token}"],
            'token with trailing slash' => ["/sondaggi/{$token}/", "/sondaggi/{$token}"],
            'token strip query in afterLogin' => ["/sondaggi/{$token}?x=1", "/sondaggi/{$token}"],
        ];
    }

    #[DataProvider('allowedTakePathsProvider')]
    public function test_after_login_accepts_survey_take_path_with_access_token(string $candidate, string $expectedPath): void
    {
        $this->assertSame($expectedPath, SafeRedirect::afterLogin($candidate));
    }

    public function test_after_login_rejects_numeric_id_and_public_index_paths(): void
    {
        $this->assertSame('/dashboard', SafeRedirect::afterLogin('/sondaggi/123'));
        $this->assertSame('/dashboard', SafeRedirect::afterLogin('/sondaggi/ricerca'));
        $this->assertSame('/dashboard', SafeRedirect::afterLogin('/sondaggi'));
    }

    public function test_is_allowed_survey_take_relative_path_matches_route_token_length(): void
    {
        $ok = str_repeat('b', 48);
        $this->assertTrue(SafeRedirect::isAllowedSurveyTakeRelativePath("/sondaggi/{$ok}"));
        $this->assertFalse(SafeRedirect::isAllowedSurveyTakeRelativePath('/sondaggi/'.str_repeat('c', 47)));
        $this->assertFalse(SafeRedirect::isAllowedSurveyTakeRelativePath('/sondaggi/ricerca'));
    }
}
