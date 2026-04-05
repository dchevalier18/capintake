<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FollowUpStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FollowUpFactory extends Factory
{
    protected $model = FollowUp::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'assigned_to' => User::factory(),
            'follow_up_type' => fake()->randomElement(array_keys(FollowUp::TYPES)),
            'scheduled_date' => now()->addDays(fake()->numberBetween(1, 30)),
            'status' => FollowUpStatus::Scheduled,
            'notes' => fake()->optional(0.5)->sentence(),
        ];
    }
}
