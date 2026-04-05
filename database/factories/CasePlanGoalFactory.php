<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Models\CasePlan;
use App\Models\CasePlanGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

class CasePlanGoalFactory extends Factory
{
    protected $model = CasePlanGoal::class;

    public function definition(): array
    {
        return [
            'case_plan_id' => CasePlan::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional(0.5)->paragraph(),
            'status' => GoalStatus::NotStarted,
            'target_date' => now()->addMonths(3),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
