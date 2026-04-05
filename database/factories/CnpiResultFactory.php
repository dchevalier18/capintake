<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CnpiIndicator;
use App\Models\CnpiResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class CnpiResultFactory extends Factory
{
    protected $model = CnpiResult::class;

    public function definition(): array
    {
        return [
            'cnpi_indicator_id' => CnpiIndicator::factory(),
            'fiscal_year' => now()->year,
            'identified_community' => fake()->city(),
            'target' => fake()->numberBetween(10, 100),
            'actual_result' => fake()->numberBetween(5, 80),
            'data_source' => fake()->optional(0.5)->word(),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
