<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnpiTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'npi_indicator_id',
        'fiscal_year',
        'target_count',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'target_count' => 'integer',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(NpiIndicator::class, 'npi_indicator_id');
    }
}
