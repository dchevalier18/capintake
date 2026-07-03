<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NpiGoal;
use App\Models\NpiIndicator;
use Illuminate\Database\Seeder;

class NpiSeeder extends Seeder
{
    /**
     * Report versions and their taxonomy data files. Rows are upserted by
     * (indicator_code, report_version) — never deleted — so re-seeding can
     * not orphan fnpi_targets, outcomes, or service mappings that reference
     * indicator ids.
     */
    protected const VERSIONS = [
        '2.1' => 'fnpi_v2_1.php',
        '3.0' => 'fnpi_v3_0.php',
    ];

    public function run(): void
    {
        foreach (self::VERSIONS as $version => $file) {
            $goals = require database_path('seeders/data/'.$file);

            foreach ($goals as $goalData) {
                $indicators = $goalData['indicators'];
                unset($goalData['indicators']);

                $goal = NpiGoal::updateOrCreate(
                    ['goal_number' => $goalData['goal_number']],
                    $goalData,
                );

                foreach ($indicators as $indicatorData) {
                    $children = $indicatorData['children'] ?? [];
                    unset($indicatorData['children']);

                    $indicator = NpiIndicator::updateOrCreate(
                        ['indicator_code' => $indicatorData['indicator_code'], 'report_version' => $version],
                        [...$indicatorData, 'npi_goal_id' => $goal->id, 'report_version' => $version],
                    );

                    foreach ($children as $childData) {
                        NpiIndicator::updateOrCreate(
                            ['indicator_code' => $childData['indicator_code'], 'report_version' => $version],
                            [
                                ...$childData,
                                'npi_goal_id' => $goal->id,
                                'parent_indicator_id' => $indicator->id,
                                'report_version' => $version,
                            ],
                        );
                    }
                }
            }
        }
    }
}
