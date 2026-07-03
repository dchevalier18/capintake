<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasReportVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CsbgStrCategory extends Model
{
    use HasFactory, HasReportVersion;

    protected $fillable = [
        'code',
        'group_code',
        'group_name',
        'name',
        'description',
        'sort_order',
        'report_version',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function communityInitiatives(): BelongsToMany
    {
        return $this->belongsToMany(CommunityInitiative::class, 'community_initiative_str_category');
    }
}
