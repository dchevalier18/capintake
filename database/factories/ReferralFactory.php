<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReferralStatus;
use App\Models\Client;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'referred_by' => User::factory(),
            'referral_date' => now(),
            'referred_to_agency' => fake()->company(),
            'referred_to_contact' => fake()->optional(0.7)->name(),
            'referred_to_phone' => fake()->optional(0.5)->phoneNumber(),
            'referral_reason' => fake()->sentence(),
            'status' => ReferralStatus::Pending,
            'follow_up_date' => now()->addWeeks(2),
        ];
    }
}
