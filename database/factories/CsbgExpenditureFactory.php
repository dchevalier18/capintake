<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CsbgExpenditure;
use Illuminate\Database\Eloquent\Factories\Factory;

class CsbgExpenditureFactory extends Factory
{
    protected $model = CsbgExpenditure::class;

    public function definition(): array
    {
        return [
            'fiscal_year' => now()->month >= 10 ? now()->year + 1 : now()->year,
            'reporting_period' => 'oct_sep',
            'domain' => fake()->randomElement(['employment', 'education', 'income_asset', 'housing', 'health_social', 'civic_engagement']),
            'csbg_funds' => fake()->randomFloat(2, 10000, 200000),
        ];
    }
}
