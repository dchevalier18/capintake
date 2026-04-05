<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CasePlanStatus;
use App\Models\CasePlan;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CasePlanFactory extends Factory
{
    protected $model = CasePlan::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'status' => CasePlanStatus::Active,
            'start_date' => now(),
            'target_completion_date' => now()->addMonths(6),
            'notes' => fake()->optional(0.5)->paragraph(),
        ];
    }
}
