<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'program_id',
        'caseworker_id',
        'status',
        'enrolled_at',
        'completed_at',
        'household_income_at_enrollment',
        'household_size_at_enrollment',
        'fpl_percent_at_enrollment',
        'income_eligible',
        'eligibility_notes',
        'denial_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'enrolled_at' => 'date',
            'completed_at' => 'date',
            'household_income_at_enrollment' => 'decimal:2',
            'household_size_at_enrollment' => 'integer',
            'fpl_percent_at_enrollment' => 'integer',
            'income_eligible' => 'boolean',
        ];
    }

    // --- Relationships ---

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function caseworker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caseworker_id');
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(Outcome::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('status', EnrollmentStatus::Active);
    }

    public function scopeInDateRange($query, $start, $end)
    {
        return $query->whereBetween('enrolled_at', [$start, $end]);
    }

    // --- Helpers ---

    public function snapshotEligibility(): void
    {
        $client = $this->client;
        $household = $client->household;

        $this->update([
            'household_size_at_enrollment' => $household->household_size,
            'household_income_at_enrollment' => $household->totalAnnualIncome(),
            'fpl_percent_at_enrollment' => $client->fplPercent(),
            'income_eligible' => $this->program->isClientEligible($client),
        ]);
    }
}
