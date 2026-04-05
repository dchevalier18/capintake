<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GoalStatus;
use App\Enums\OutcomeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CasePlanGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_plan_id',
        'npi_indicator_id',
        'title',
        'description',
        'status',
        'target_date',
        'achieved_date',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => GoalStatus::class,
            'target_date' => 'date',
            'achieved_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (CasePlanGoal $goal) {
            // When a goal is marked achieved and has an NPI indicator,
            // auto-create or update the corresponding Outcome record.
            if ($goal->isDirty('status')
                && $goal->status === GoalStatus::Achieved
                && $goal->npi_indicator_id
            ) {
                $casePlan = $goal->casePlan;
                Outcome::updateOrCreate(
                    [
                        'client_id' => $casePlan->client_id,
                        'npi_indicator_id' => $goal->npi_indicator_id,
                    ],
                    [
                        'status' => OutcomeStatus::Achieved,
                        'achieved_date' => $goal->achieved_date ?? now(),
                        'notes' => "Auto-created from case plan goal: {$goal->title}",
                    ],
                );
            }
        });
    }

    public function casePlan(): BelongsTo
    {
        return $this->belongsTo(CasePlan::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(NpiIndicator::class, 'npi_indicator_id');
    }
}
