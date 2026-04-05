<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CsbgReportSetting extends Model
{
    use HasFactory;

    protected $table = 'csbg_report_settings';

    protected $fillable = [
        'entity_name',
        'state',
        'uei',
        'reporting_period',
        'current_fiscal_year',
        'total_csbg_allocation',
        'additional_settings',
    ];

    protected function casts(): array
    {
        return [
            'current_fiscal_year' => 'integer',
            'total_csbg_allocation' => 'decimal:2',
            'additional_settings' => 'array',
        ];
    }

    /**
     * Get the singleton instance, creating a default if none exists.
     */
    public static function current(): static
    {
        return static::firstOrCreate([], [
            'entity_name' => AgencySetting::current()?->agency_name ?? 'Community Action Agency',
            'state' => AgencySetting::current()?->agency_state ?? 'PA',
            'reporting_period' => 'oct_sep',
            'current_fiscal_year' => now()->month >= 10 ? now()->year + 1 : now()->year,
        ]);
    }
}
