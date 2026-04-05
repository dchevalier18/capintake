<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CnpiType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CnpiIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'indicator_code',
        'name',
        'description',
        'cnpi_type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'cnpi_type' => CnpiType::class,
            'sort_order' => 'integer',
        ];
    }

    public function results(): HasMany
    {
        return $this->hasMany(CnpiResult::class);
    }
}
