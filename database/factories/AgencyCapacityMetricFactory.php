<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AgencyCapacityMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgencyCapacityMetricFactory extends Factory
{
    protected $model = AgencyCapacityMetric::class;

    public function definition(): array
    {
        return [
            'fiscal_year' => now()->year,
            'metric_type' => fake()->randomElement(array_keys(AgencyCapacityMetric::TYPES)),
            'metric_key' => fake()->unique()->word(),
            'metric_value' => fake()->randomFloat(2, 0, 1000),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
