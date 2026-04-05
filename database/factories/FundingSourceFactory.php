<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FundingSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundingSourceFactory extends Factory
{
    protected $model = FundingSource::class;

    public function definition(): array
    {
        return [
            'fiscal_year' => now()->year,
            'source_type' => fake()->randomElement(array_keys(FundingSource::SOURCE_TYPES)),
            'source_name' => fake()->company(),
            'cfda_number' => fake()->optional(0.3)->numerify('##.###'),
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
