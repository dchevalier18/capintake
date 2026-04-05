<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfSufficiencyAssessment extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'client_id',
        'assessed_by',
        'assessment_date',
        'domain_scores',
        'total_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assessment_date' => 'date',
            'domain_scores' => 'array',
            'total_score' => 'integer',
        ];
    }

    /**
     * Standard self-sufficiency domains.
     */
    public const DOMAINS = [
        'employment' => 'Employment',
        'income' => 'Income',
        'housing' => 'Housing',
        'food' => 'Food',
        'childcare' => 'Childcare',
        'transportation' => 'Transportation',
        'health' => 'Health',
        'education' => 'Education',
        'family_relations' => 'Family Relations',
        'social_networks' => 'Social Networks',
        'legal' => 'Legal',
    ];

    protected static function booted(): void
    {
        static::saving(function (SelfSufficiencyAssessment $assessment) {
            $scores = $assessment->domain_scores ?? [];
            $assessment->total_score = (int) array_sum($scores);
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assessedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
