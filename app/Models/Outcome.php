<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OutcomeStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outcome extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'npi_indicator_id',
        'enrollment_id',
        'service_record_id',
        'status',
        'achieved_date',
        'target_date',
        'baseline_value',
        'result_value',
        'notes',
        'verified_by',
        'verified_at',
        'fiscal_year',
    ];

    protected function casts(): array
    {
        return [
            'status' => OutcomeStatus::class,
            'achieved_date' => 'date',
            'target_date' => 'date',
            'verified_at' => 'datetime',
            'fiscal_year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Outcome $outcome) {
            if ($outcome->fiscal_year === null || $outcome->fiscal_year === 0) {
                $outcome->fiscal_year = static::computeFiscalYear($outcome);
            }
        });
    }

    /**
     * Compute the fiscal year from the achieved_date or current date.
     * Uses CSBG reporting period convention (default Oct-Sep).
     */
    protected static function computeFiscalYear(Outcome $outcome): int
    {
        $date = $outcome->achieved_date ?? now();

        $setting = CsbgReportSetting::current();
        $period = $setting->reporting_period ?? 'oct_sep';

        $startMonth = match ($period) {
            'oct_sep' => 10,
            'jul_jun' => 7,
            'jan_dec' => 1,
            default => 10,
        };

        $month = $date->month;
        $year = $date->year;

        if ($startMonth > 1 && $month >= $startMonth) {
            return $year + 1;
        }

        return $year;
    }

    // --- Relationships ---

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(NpiIndicator::class, 'npi_indicator_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function serviceRecord(): BelongsTo
    {
        return $this->belongsTo(ServiceRecord::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // --- Scopes ---

    public function scopeAchieved($query)
    {
        return $query->where('status', OutcomeStatus::Achieved);
    }

    public function scopeForIndicator($query, int $indicatorId)
    {
        return $query->where('npi_indicator_id', $indicatorId);
    }

    public function scopeInFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeInDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('achieved_date', [$start, $end]);
    }
}
