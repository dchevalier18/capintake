<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

class HouseholdFactory extends Factory
{
    protected $model = Household::class;

    public function definition(): array
    {
        return [
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional(0.2)->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip' => fake()->postcode(),
            'county' => fake()->optional(0.8)->city() . ' County',
            'housing_type' => fake()->randomElement(['own', 'rent', 'other_permanent', 'homeless', 'other']),
            'household_size' => fake()->numberBetween(1, 6),
        ];
    }

    public function homeless(): static
    {
        return $this->state(fn () => [
            'address_line_1' => 'No Fixed Address',
            'housing_type' => 'homeless',
        ]);
    }
}
