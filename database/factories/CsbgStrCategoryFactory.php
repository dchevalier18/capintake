<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CsbgStrCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CsbgStrCategoryFactory extends Factory
{
    protected $model = CsbgStrCategory::class;

    public function definition(): array
    {
        return [
            'code' => 'STR ' . fake()->unique()->numerify('#?'),
            'group_code' => 'STR ' . fake()->numberBetween(1, 8),
            'group_name' => fake()->words(3, true),
            'name' => fake()->sentence(3),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
