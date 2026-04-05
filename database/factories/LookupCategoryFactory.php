<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LookupCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class LookupCategoryFactory extends Factory
{
    protected $model = LookupCategory::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'is_system' => false,
            'allow_custom' => true,
            'sort_order' => 0,
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => [
            'is_system' => true,
        ]);
    }
}
