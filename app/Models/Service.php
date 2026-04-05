<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'name',
        'code',
        'description',
        'unit_of_measure',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // --- Relationships ---

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function npiIndicators(): BelongsToMany
    {
        return $this->belongsToMany(NpiIndicator::class, 'npi_indicator_service')
            ->withTimestamps();
    }

    public function srvCategories(): BelongsToMany
    {
        return $this->belongsToMany(CsbgSrvCategory::class, 'service_srv_category');
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
