<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OutcomeStatus;
use App\Models\Client;
use App\Models\NpiIndicator;
use App\Models\Outcome;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutcomeFactory extends Factory
{
    protected $model = Outcome::class;

    public function definition(): array
    {
        $status = fake()->randomElement(OutcomeStatus::cases());
        $achievedDate = $status === OutcomeStatus::Achieved || $status === OutcomeStatus::Maintained
            ? fake()->dateTimeBetween('-6 months', 'now')
            : null;

        return [
            'client_id' => Client::factory(),
            'npi_indicator_id' => NpiIndicator::factory(),
            'enrollment_id' => null,
            'service_record_id' => null,
            'status' => $status,
            'achieved_date' => $achievedDate,
            'target_date' => fake()->optional(0.5)->dateTimeBetween('now', '+6 months'),
            'baseline_value' => fake()->optional(0.3)->word(),
            'result_value' => fake()->optional(0.3)->word(),
            'notes' => fake()->optional(0.4)->sentence(),
            'verified_by' => null,
            'verified_at' => null,
            'fiscal_year' => now()->month >= 10 ? now()->year + 1 : now()->year,
        ];
    }

    public function achieved(): static
    {
        return $this->state(fn () => [
            'status' => OutcomeStatus::Achieved,
            'achieved_date' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => OutcomeStatus::InProgress,
            'achieved_date' => null,
        ]);
    }

    public function notAchieved(): static
    {
        return $this->state(fn () => [
            'status' => OutcomeStatus::NotAchieved,
            'achieved_date' => null,
        ]);
    }
}
