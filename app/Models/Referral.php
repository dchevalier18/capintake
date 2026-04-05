<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReferralStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referral extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'referred_by',
        'referral_date',
        'referred_to_agency',
        'referred_to_contact',
        'referred_to_phone',
        'referral_reason',
        'status',
        'follow_up_date',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReferralStatus::class,
            'referral_date' => 'date',
            'follow_up_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function referredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }
}
