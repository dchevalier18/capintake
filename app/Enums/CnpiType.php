<?php

declare(strict_types=1);

namespace App\Enums;

enum CnpiType: string
{
    case CountOfChange = 'count_of_change';
    case RateOfChange = 'rate_of_change';

    public function label(): string
    {
        return match ($this) {
            self::CountOfChange => 'Count of Change',
            self::RateOfChange => 'Rate of Change',
        };
    }
}
