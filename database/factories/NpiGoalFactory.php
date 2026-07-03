<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NpiGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

class NpiGoalFactory extends Factory
{
    protected $model = NpiGoal::class;

    public function definition(): array
    {
        return [
            // Start above the 7 seeded federal goals so factory-created goals
            // never collide with NpiSeeder rows on the unique goal_number.
            'goal_number' => fake()->unique()->numberBetween(100, 10000),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
        ];
    }
}
