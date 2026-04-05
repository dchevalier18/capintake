<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FederalPovertyLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class FederalPovertyLevelFactory extends Factory
{
    protected $model = FederalPovertyLevel::class;

    public function definition(): array
    {
        return [
            'year' => now()->year,
            'region' => 'continental',
            'household_size' => fake()->numberBetween(1, 8),
            'poverty_guideline' => fake()->numberBetween(15000, 60000),
        ];
    }
}
