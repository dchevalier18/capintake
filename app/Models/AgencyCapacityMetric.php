<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyCapacityMetric extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'fiscal_year',
        'metric_type',
        'metric_key',
        'metric_value',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'metric_value' => 'decimal:2',
        ];
    }

    /**
     * Metric types map to Module 2B sections.
     */
    public const TYPES = [
        'capacity_building_hours' => 'Hours of Agency Capacity Building',
        'volunteer_hours' => 'Volunteer Hours',
        'staff_certifications' => 'Staff Certifications',
        'partner_organizations' => 'Partner Organizations',
    ];

    /**
     * Get all metrics for a fiscal year grouped by type.
     */
    public static function forFiscalYear(int $year): array
    {
        return static::where('fiscal_year', $year)
            ->orderBy('metric_type')
            ->orderBy('metric_key')
            ->get()
            ->groupBy('metric_type')
            ->map(fn ($group) => $group->mapWithKeys(
                fn ($m) => [$m->metric_key => (float) $m->metric_value]
            )->toArray())
            ->toArray();
    }
}
