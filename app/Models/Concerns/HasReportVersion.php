<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\CsbgReportSetting;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared by the CSBG taxonomy models (FNPI/SRV/STR/CNPI). Each taxonomy row
 * belongs to a CSBG Annual Report version ('2.1' or '3.0'); the agency's
 * active version lives on CsbgReportSetting.
 */
trait HasReportVersion
{
    /**
     * Scope to a report version; defaults to the agency's configured version.
     */
    public function scopeForVersion(Builder $query, ?string $version = null): Builder
    {
        return $query->where('report_version', $version ?? static::activeReportVersion());
    }

    /**
     * The agency's currently selected CSBG Annual Report version.
     */
    public static function activeReportVersion(): string
    {
        return CsbgReportSetting::current()->report_version ?? '2.1';
    }
}
