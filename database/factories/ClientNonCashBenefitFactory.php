<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientNonCashBenefit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientNonCashBenefitFactory extends Factory
{
    protected $model = ClientNonCashBenefit::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'benefit_type' => fake()->randomElement(['snap', 'wic', 'liheap', 'housing_choice_voucher', 'public_housing']),
            'effective_date' => fake()->optional()->date(),
            'expiration_date' => fake()->optional()->date(),
            'is_active' => true,
        ];
    }
}
