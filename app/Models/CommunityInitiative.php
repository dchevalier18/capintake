<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityInitiative extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'year_number',
        'problem_statement',
        'goal_statement',
        'domain',
        'identified_community',
        'expected_duration',
        'partnership_type',
        'partners',
        'strategies',
        'progress_status',
        'impact_narrative',
        'final_status',
        'lessons_learned',
        'fiscal_year',
    ];

    protected function casts(): array
    {
        return [
            'year_number' => 'integer',
            'fiscal_year' => 'integer',
            'strategies' => 'array',
        ];
    }

    // --- Relationships ---

    public function cnpiResults(): HasMany
    {
        return $this->hasMany(CnpiResult::class);
    }

    public function strCategories(): BelongsToMany
    {
        return $this->belongsToMany(CsbgStrCategory::class, 'community_initiative_str_category');
    }
}
