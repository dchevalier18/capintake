<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IntakeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'household_id',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'ssn_encrypted',
        'ssn_last_four',
        'phone',
        'email',
        'gender',
        'race',
        'ethnicity',
        'is_veteran',
        'is_disabled',
        'is_head_of_household',
        'preferred_language',
        'relationship_to_head',
        'notes',
        'intake_status',
        'intake_step',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'ssn_encrypted' => 'encrypted',
            'is_veteran' => 'boolean',
            'is_disabled' => 'boolean',
            'is_head_of_household' => 'boolean',
            'intake_status' => IntakeStatus::class,
            'intake_step' => 'integer',
        ];
    }

    // --- Relationships ---

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function incomeRecords(): HasMany
    {
        return $this->hasMany(IncomeRecord::class);
    }

    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', 'active');
    }

    // --- Scopes ---

    public function scopeComplete($query)
    {
        return $query->where('intake_status', IntakeStatus::Complete);
    }

    public function scopeDraft($query)
    {
        return $query->where('intake_status', IntakeStatus::Draft);
    }

    // --- Helpers ---

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function totalAnnualIncome(): float
    {
        return (float) $this->incomeRecords()->sum('annual_amount');
    }

    /**
     * Check if client is income-eligible for a given program.
     * Returns the FPL percentage or null if FPL data is missing.
     */
    public function fplPercent(int $year = null): ?int
    {
        $year ??= now()->year;
        $householdSize = $this->household->household_size;

        $fpl = FederalPovertyLevel::where('year', $year)
            ->where('household_size', min($householdSize, 8))
            ->where('region', 'continental')
            ->first();

        if (! $fpl) {
            return null;
        }

        $totalIncome = $this->household->totalAnnualIncome();

        return (int) round(($totalIncome / $fpl->poverty_guideline) * 100);
    }
}
