<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LookupValue;
use Illuminate\Support\Collection;

class Lookup
{
    /**
     * Get active values for a category as key => label array for Filament Selects.
     */
    public static function options(string $categoryKey): array
    {
        return LookupValue::whereHas('category', fn ($q) => $q->where('key', $categoryKey))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->pluck('label', 'key')
            ->toArray();
    }

    /**
     * Get the display label for a stored value key.
     */
    public static function label(string $categoryKey, ?string $valueKey): ?string
    {
        if ($valueKey === null) {
            return null;
        }

        return LookupValue::whereHas('category', fn ($q) => $q->where('key', $categoryKey))
            ->where('key', $valueKey)
            ->value('label');
    }

    /**
     * Get the CSBG report label for a stored value.
     * Falls back to the display label if no csbg_report_code is set.
     */
    public static function csbgLabel(string $categoryKey, ?string $valueKey): ?string
    {
        if ($valueKey === null) {
            return null;
        }

        $value = LookupValue::whereHas('category', fn ($q) => $q->where('key', $categoryKey))
            ->where('key', $valueKey)
            ->first();

        return $value?->csbg_report_code ?? $value?->label;
    }

    /**
     * Get all values for a category including inactive (for admin).
     */
    public static function allValues(string $categoryKey): Collection
    {
        return LookupValue::whereHas('category', fn ($q) => $q->where('key', $categoryKey))
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }
}
