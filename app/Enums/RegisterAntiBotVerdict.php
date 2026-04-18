<?php

declare(strict_types=1);

namespace App\Enums;

enum RegisterAntiBotVerdict: string
{
    case Pass = 'pass';
    case Challenge = 'challenge';
    case Block = 'block';
}
