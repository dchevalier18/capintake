<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundingSource extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'fiscal_year',
        'source_type',
        'source_name',
        'cfda_number',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    public const SOURCE_TYPES = [
        'federal_csbg' => 'Federal CSBG',
        'federal_other' => 'Other Federal',
        'state' => 'State',
        'local' => 'Local',
        'private' => 'Private',
        'in_kind' => 'In-Kind',
    ];
}
