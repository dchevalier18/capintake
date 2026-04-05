<?php

declare(strict_types=1);

namespace App\Enums;

enum CasePlanStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Closed => 'Closed',
        };
    }
}
