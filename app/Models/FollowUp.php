<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FollowUpStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FollowUp extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'client_id',
        'assigned_to',
        'follow_up_type',
        'scheduled_date',
        'completed_date',
        'status',
        'notes',
        'related_type',
        'related_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => FollowUpStatus::class,
            'scheduled_date' => 'date',
            'completed_date' => 'date',
        ];
    }

    public const TYPES = [
        'outcome_check' => 'Outcome Check',
        'referral_follow_up' => 'Referral Follow-Up',
        'general' => 'General',
        'self_sufficiency' => 'Self-Sufficiency Review',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('status', FollowUpStatus::Scheduled)
            ->whereBetween('scheduled_date', [now()->format('Y-m-d'), now()->addDays($days)->format('Y-m-d')]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', FollowUpStatus::Scheduled)
            ->where('scheduled_date', '<', now()->format('Y-m-d'));
    }
}
