<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasReportVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CsbgSrvCategory extends Model
{
    use HasFactory, HasReportVersion;

    protected $fillable = [
        'code',
        'domain',
        'group_name',
        'name',
        'sort_order',
        'report_version',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_srv_category');
    }
}
