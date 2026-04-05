<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CnpiResult extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'cnpi_indicator_id',
        'community_initiative_id',
        'fiscal_year',
        'identified_community',
        'target',
        'actual_result',
        'performance_accuracy',
        'baseline_value',
        'expected_change_pct',
        'actual_change_pct',
        'data_source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'target' => 'decimal:2',
            'actual_result' => 'decimal:2',
            'performance_accuracy' => 'decimal:2',
            'baseline_value' => 'decimal:2',
            'expected_change_pct' => 'decimal:2',
            'actual_change_pct' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CnpiResult $result) {
            if ($result->target && $result->target > 0 && $result->actual_result !== null) {
                $result->performance_accuracy = round(((float) $result->actual_result / (float) $result->target) * 100, 2);
            }
        });
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(CnpiIndicator::class, 'cnpi_indicator_id');
    }

    public function communityInitiative(): BelongsTo
    {
        return $this->belongsTo(CommunityInitiative::class);
    }
}
