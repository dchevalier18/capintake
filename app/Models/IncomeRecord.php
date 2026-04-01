<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IncomeFrequency;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomeRecord extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'household_member_id',
        'source',
        'source_description',
        'amount',
        'frequency',
        'annual_amount',
        'is_verified',
        'verification_method',
        'verified_at',
        'effective_date',
        'expiration_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'annual_amount' => 'decimal:2',
            'frequency' => IncomeFrequency::class,
            'is_verified' => 'boolean',
            'verified_at' => 'date',
            'effective_date' => 'date',
            'expiration_date' => 'date',
        ];
    }

    // --- Relationships ---

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function householdMember(): BelongsTo
    {
        return $this->belongsTo(HouseholdMember::class);
    }

    // --- Lifecycle ---

    protected static function booted(): void
    {
        static::saving(function (IncomeRecord $record) {
            $record->annual_amount = $record->calculateAnnualAmount();
        });
    }

    // --- Helpers ---

    public function calculateAnnualAmount(): float
    {
        if (! $this->frequency) {
            return (float) $this->amount;
        }

        return round((float) $this->amount * $this->frequency->annualMultiplier(), 2);
    }

    public function isExpired(): bool
    {
        if (! $this->expiration_date) {
            return false;
        }

        return $this->expiration_date->isPast();
    }
}
