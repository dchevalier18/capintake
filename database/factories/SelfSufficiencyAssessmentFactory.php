<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\SelfSufficiencyAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SelfSufficiencyAssessmentFactory extends Factory
{
    protected $model = SelfSufficiencyAssessment::class;

    public function definition(): array
    {
        $scores = [];
        foreach (array_keys(SelfSufficiencyAssessment::DOMAINS) as $domain) {
            $scores[$domain] = fake()->numberBetween(1, 5);
        }

        return [
            'client_id' => Client::factory(),
            'assessed_by' => User::factory(),
            'assessment_date' => now(),
            'domain_scores' => $scores,
            'notes' => fake()->optional(0.5)->paragraph(),
        ];
    }
}
