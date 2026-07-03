<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FederalPovertyLevel;
use Illuminate\Database\Seeder;

class FederalPovertyLevelSeeder extends Seeder
{
    /**
     * HHS Poverty Guidelines by year. Add each new year when HHS publishes
     * it (typically mid-January) — reports for a year fall back to the
     * latest seeded year until then.
     *
     * Sources:
     * - https://aspe.hhs.gov/topics/poverty-economic-mobility/poverty-guidelines
     * - 2026: Federal Register 2026-00755 (Jan 15, 2026)
     */
    protected const GUIDELINES = [
        2025 => [
            'continental' => [
                1 => 15650,
                2 => 21150,
                3 => 26650,
                4 => 32150,
                5 => 37650,
                6 => 43150,
                7 => 48650,
                8 => 54150,
                // Each additional person: +$5,500
            ],
            'alaska' => [
                1 => 19560,
                2 => 26430,
                3 => 33300,
                4 => 40170,
                5 => 47040,
                6 => 53910,
                7 => 60780,
                8 => 67650,
            ],
            'hawaii' => [
                1 => 18000,
                2 => 24330,
                3 => 30660,
                4 => 36990,
                5 => 43320,
                6 => 49650,
                7 => 55980,
                8 => 62310,
            ],
        ],
        2026 => [
            'continental' => [
                1 => 15960,
                2 => 21640,
                3 => 27320,
                4 => 33000,
                5 => 38680,
                6 => 44360,
                7 => 50040,
                8 => 55720,
                // Each additional person: +$5,680
            ],
            'alaska' => [
                1 => 19950,
                2 => 27050,
                3 => 34150,
                4 => 41250,
                5 => 48350,
                6 => 55450,
                7 => 62550,
                8 => 69650,
            ],
            'hawaii' => [
                1 => 18360,
                2 => 24890,
                3 => 31420,
                4 => 37950,
                5 => 44480,
                6 => 51010,
                7 => 57540,
                8 => 64070,
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::GUIDELINES as $year => $regions) {
            foreach ($regions as $region => $levels) {
                foreach ($levels as $size => $amount) {
                    FederalPovertyLevel::updateOrCreate(
                        [
                            'year' => $year,
                            'household_size' => $size,
                            'region' => $region,
                        ],
                        [
                            'poverty_guideline' => $amount,
                        ]
                    );
                }
            }
        }
    }
}
