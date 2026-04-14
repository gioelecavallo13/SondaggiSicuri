<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\SurveyPrivacyMode;
use App\Support\SurveyTakePrivacyNotice;
use PHPUnit\Framework\TestCase;

class SurveyTakePrivacyNoticeTest extends TestCase
{
    public function test_for_mode_returns_heading_and_body_for_each_case(): void
    {
        foreach (SurveyPrivacyMode::cases() as $mode) {
            $out = SurveyTakePrivacyNotice::forMode($mode);
            $this->assertArrayHasKey('heading', $out);
            $this->assertArrayHasKey('body', $out);
            $this->assertNotSame('', trim($out['heading']));
            $this->assertNotSame('', trim($out['body']));
        }
    }
}
