<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceRecord extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'service_id',
        'enrollment_id',
        'provided_by',
        'service_date',
        'quantity',
        'value',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'quantity' => 'decimal:2',
            'value' => 'decimal:2',
        ];
    }

    // --- Relationships ---

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provided_by');
    }

    // --- Scopes ---

    public function scopeInDateRange($query, $start, $end)
    {
        return $query->whereBetween('service_date', [$start, $end]);
    }

    public function scopeForNpiIndicator($query, int $indicatorId)
    {
        return $query->whereHas('service', function ($q) use ($indicatorId) {
            $q->whereHas('npiIndicators', function ($q2) use ($indicatorId) {
                $q2->where('npi_indicators.id', $indicatorId);
            });
        });
    }
}
