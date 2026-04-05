<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LookupCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_system',
        'allow_custom',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'allow_custom' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(LookupValue::class)->orderBy('sort_order')->orderBy('label');
    }

    public function activeValues(): HasMany
    {
        return $this->hasMany(LookupValue::class)->where('is_active', true)->orderBy('sort_order')->orderBy('label');
    }
}
