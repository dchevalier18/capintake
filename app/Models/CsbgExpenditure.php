<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CsbgExpenditure extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscal_year',
        'reporting_period',
        'domain',
        'csbg_funds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'csbg_funds' => 'decimal:2',
        ];
    }
}
