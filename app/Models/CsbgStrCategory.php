<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CsbgStrCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'group_code',
        'group_name',
        'name',
        'description',
        'sort_order',
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
