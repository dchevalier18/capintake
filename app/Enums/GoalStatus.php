<?php

declare(strict_types=1);

namespace App\Enums;

enum GoalStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Achieved = 'achieved';
    case NotAchieved = 'not_achieved';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::InProgress => 'In Progress',
            self::Achieved => 'Achieved',
            self::NotAchieved => 'Not Achieved',
        };
    }
}
