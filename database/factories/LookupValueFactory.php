<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LookupCategory;
use App\Models\LookupValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class LookupValueFactory extends Factory
{
    protected $model = LookupValue::class;

    public function definition(): array
    {
        return [
            'lookup_category_id' => LookupCategory::factory(),
            'key' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'csbg_report_code' => null,
            'is_active' => true,
            'is_system' => false,
            'sort_order' => 0,
        ];
    }
}
