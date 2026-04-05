<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NpiIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'npi_goal_id',
        'parent_indicator_id',
        'indicator_code',
        'name',
        'description',
        'indicator_type',
        'is_aggregate',
    ];

    protected function casts(): array
    {
        return [
            'is_aggregate' => 'boolean',
        ];
    }

    // --- Relationships ---

    public function goal(): BelongsTo
    {
        return $this->belongsTo(NpiGoal::class, 'npi_goal_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_indicator_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_indicator_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'npi_indicator_service')
            ->withTimestamps();
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(Outcome::class);
    }

    // --- Helpers ---

    /**
     * Count unduplicated clients who received services mapped to this indicator
     * within a date range.
     */
    public function unduplicatedClientCount(string $startDate, string $endDate): int
    {
        return ServiceRecord::query()
            ->whereHas('service.npiIndicators', function ($q) {
                $q->where('npi_indicators.id', $this->id);
            })
            ->whereBetween('service_date', [$startDate, $endDate])
            ->distinct('client_id')
            ->count('client_id');
    }
}
