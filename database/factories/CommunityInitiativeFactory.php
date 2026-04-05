<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommunityInitiative;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityInitiativeFactory extends Factory
{
    protected $model = CommunityInitiative::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'year_number' => fake()->numberBetween(1, 5),
            'problem_statement' => fake()->paragraph(),
            'goal_statement' => fake()->paragraph(),
            'domain' => fake()->randomElement(['employment', 'education', 'income_asset', 'housing', 'health_social', 'civic_engagement']),
            'identified_community' => fake()->city(),
            'expected_duration' => fake()->randomElement(['1 year', '2 years', '3 years']),
            'partnership_type' => fake()->randomElement(['independent', 'core_organizer', 'active_partner']),
            'progress_status' => fake()->randomElement(['no_outcomes', 'interim', 'final']),
            'fiscal_year' => now()->month >= 10 ? now()->year + 1 : now()->year,
        ];
    }
}
