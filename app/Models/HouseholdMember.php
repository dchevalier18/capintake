<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\EncryptedDate;
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
        'birth_year',
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
            'date_of_birth' => EncryptedDate::class,
            'birth_year' => 'integer',
            'employment_status' => 'string',
            'is_veteran' => 'boolean',
            'is_disabled' => 'boolean',
            'is_student' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (HouseholdMember $model) {
            $dob = $model->date_of_birth;
            if ($dob instanceof \DateTimeInterface) {
                $model->birth_year = (int) $dob->format('Y');
            }
        });
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
