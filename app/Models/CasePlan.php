<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CasePlanStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CasePlan extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'created_by',
        'title',
        'status',
        'start_date',
        'target_completion_date',
        'completed_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CasePlanStatus::class,
            'start_date' => 'date',
            'target_completion_date' => 'date',
            'completed_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(CasePlanGoal::class)->orderBy('sort_order');
    }
}
