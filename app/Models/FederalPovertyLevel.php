<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FederalPovertyLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'household_size',
        'poverty_guideline',
        'region',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'household_size' => 'integer',
            'poverty_guideline' => 'integer',
        ];
    }

    /**
     * Get the poverty guideline for a given household size and year.
     *
     * If the requested year has no guideline rows (HHS publishes each year's
     * table mid-January), falls back to the most recent seeded year so
     * eligibility screening never silently returns null for a missing year.
     */
    public static function guidelineFor(int $householdSize, ?int $year = null, string $region = 'continental'): ?int
    {
        $year = static::resolveYear($year, $region);

        if ($year === null) {
            return null;
        }

        $size = min($householdSize, 8);

        $fpl = static::where('year', $year)
            ->where('household_size', $size)
            ->where('region', $region)
            ->first();

        if (! $fpl) {
            return null;
        }

        // For household sizes > 8, add the per-person increment
        if ($householdSize > 8) {
            $base = $fpl->poverty_guideline;
            $increment = static::perPersonIncrement($year, $region);

            return $base + (($householdSize - 8) * $increment);
        }

        return $fpl->poverty_guideline;
    }

    /**
     * Resolve the guideline year to use: the requested year if seeded,
     * otherwise the most recent seeded year at or before it, otherwise
     * the latest seeded year overall. Null when no rows exist at all.
     */
    public static function resolveYear(?int $year, string $region = 'continental'): ?int
    {
        $year ??= now()->year;

        if (static::where('year', $year)->where('region', $region)->exists()) {
            return $year;
        }

        $fallback = static::where('region', $region)
            ->where('year', '<=', $year)
            ->max('year');

        if ($fallback !== null) {
            return (int) $fallback;
        }

        $latest = static::where('region', $region)->max('year');

        return $latest !== null ? (int) $latest : null;
    }

    /**
     * The most recent year with seeded guidelines (any region).
     */
    public static function latestYearAvailable(): ?int
    {
        $year = static::max('year');

        return $year !== null ? (int) $year : null;
    }

    /**
     * Whether guidelines for the current calendar year are seeded.
     */
    public static function isCurrentYearSeeded(): bool
    {
        return static::where('year', now()->year)->exists();
    }

    /**
     * The per-person increment for households larger than 8.
     * Calculated as the difference between size 8 and size 7.
     */
    public static function perPersonIncrement(int $year, string $region = 'continental'): int
    {
        $year = static::resolveYear($year, $region) ?? $year;

        $size8 = static::where('year', $year)->where('household_size', 8)->where('region', $region)->value('poverty_guideline');
        $size7 = static::where('year', $year)->where('household_size', 7)->where('region', $region)->value('poverty_guideline');

        if ($size8 && $size7) {
            return $size8 - $size7;
        }

        return 5680; // 2026 continental US increment as last resort
    }

    /**
     * Calculate FPL percentage for a given income and household size.
     */
    public static function fplPercent(float $annualIncome, int $householdSize, ?int $year = null, string $region = 'continental'): ?int
    {
        $guideline = static::guidelineFor($householdSize, $year, $region);

        if (! $guideline || $guideline === 0) {
            return null;
        }

        return (int) round(($annualIncome / $guideline) * 100);
    }
}
