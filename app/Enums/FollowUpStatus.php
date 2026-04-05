<?php

declare(strict_types=1);

namespace App\Enums;

enum FollowUpStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Missed = 'missed';
    case Rescheduled = 'rescheduled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Missed => 'Missed',
            self::Rescheduled => 'Rescheduled',
        };
    }
}
