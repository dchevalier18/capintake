<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Household extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip',
        'county',
        'housing_type',
        'household_type',
        'household_size',
    ];

    protected function casts(): array
    {
        return [
            'household_size' => 'integer',
        ];
    }

    // --- Relationships ---

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(HouseholdMember::class);
    }

    // --- Helpers ---

    public function fullAddress(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state . ' ' . $this->zip,
        ]);

        return implode(', ', $parts);
    }

    public function recalculateSize(): void
    {
        // Head of household (client) + other members
        $clientCount = $this->clients()->count();
        $memberCount = $this->members()->count();
        $this->update(['household_size' => $clientCount + $memberCount]);
    }

    public function totalAnnualIncome(): float
    {
        $clientIncome = $this->clients()
            ->with('incomeRecords')
            ->get()
            ->flatMap->incomeRecords
            ->sum('annual_amount');

        $memberIncome = $this->members()
            ->with('incomeRecords')
            ->get()
            ->flatMap->incomeRecords
            ->sum('annual_amount');

        return (float) ($clientIncome + $memberIncome);
    }
}
