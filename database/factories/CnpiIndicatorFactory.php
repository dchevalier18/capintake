<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CnpiType;
use App\Models\CnpiIndicator;
use Illuminate\Database\Eloquent\Factories\Factory;

class CnpiIndicatorFactory extends Factory
{
    protected $model = CnpiIndicator::class;

    public function definition(): array
    {
        return [
            'domain' => fake()->randomElement(['employment', 'education', 'income_asset', 'housing', 'health_social', 'civic_engagement']),
            'indicator_code' => 'CNPI-' . fake()->unique()->numerify('##'),
            'name' => fake()->sentence(4),
            'cnpi_type' => fake()->randomElement(CnpiType::cases()),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
