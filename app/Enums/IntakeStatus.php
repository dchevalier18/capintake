<?php

declare(strict_types=1);

namespace App\Enums;

enum IntakeStatus: string
{
    case Draft = 'draft';
    case Complete = 'complete';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Complete => 'Complete',
        };
    }
}
