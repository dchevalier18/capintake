<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseholdMember extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'household_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'race',
        'ethnicity',
        'relationship_to_client',
        'employment_status',
        'is_veteran',
        'is_disabled',
        'is_student',
        'education_level',
        'health_insurance',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'employment_status' => EmploymentStatus::class,
            'is_veteran' => 'boolean',
            'is_disabled' => 'boolean',
            'is_student' => 'boolean',
        ];
    }

    // --- Relationships ---

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function incomeRecords(): HasMany
    {
        return $this->hasMany(IncomeRecord::class);
    }

    // --- Helpers ---

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }
}
