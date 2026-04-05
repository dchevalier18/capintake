<?php

declare(strict_types=1);

namespace App\Enums;

enum ReferralStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Completed = 'completed';
    case Declined = 'declined';
    case NoResponse = 'no_response';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Completed => 'Completed',
            self::Declined => 'Declined',
            self::NoResponse => 'No Response',
        };
    }
}
