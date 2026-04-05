<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CsbgReportSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class CsbgReportSettingFactory extends Factory
{
    protected $model = CsbgReportSetting::class;

    public function definition(): array
    {
        return [
            'entity_name' => fake()->company(),
            'state' => fake()->stateAbbr(),
            'uei' => strtoupper(fake()->bothify('????????????')),
            'reporting_period' => 'oct_sep',
            'current_fiscal_year' => now()->year,
            'total_csbg_allocation' => fake()->randomFloat(2, 100000, 5000000),
        ];
    }
}
