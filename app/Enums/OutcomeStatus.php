<?php

declare(strict_types=1);

namespace App\Enums;

enum OutcomeStatus: string
{
    case InProgress = 'in_progress';
    case Achieved = 'achieved';
    case NotAchieved = 'not_achieved';
    case Maintained = 'maintained';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            self::Achieved => 'Achieved',
            self::NotAchieved => 'Not Achieved',
            self::Maintained => 'Maintained',
        };
    }
}
