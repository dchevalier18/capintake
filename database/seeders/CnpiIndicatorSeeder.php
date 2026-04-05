<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CnpiIndicator;
use Illuminate\Database\Seeder;

class CnpiIndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $indicators = [
            // CNPI 1 — Employment
            ['domain' => 'employment', 'indicator_code' => 'CNPI-1a', 'name' => 'Number of jobs created for people with low incomes', 'cnpi_type' => 'count_of_change', 'sort_order' => 1],
            ['domain' => 'employment', 'indicator_code' => 'CNPI-1b', 'name' => 'Number of job opportunities maintained', 'cnpi_type' => 'count_of_change', 'sort_order' => 2],
            ['domain' => 'employment', 'indicator_code' => 'CNPI-1c', 'name' => 'Number of "living wage" jobs created', 'cnpi_type' => 'count_of_change', 'sort_order' => 3],
            ['domain' => 'employment', 'indicator_code' => 'CNPI-1d', 'name' => 'Number of "living wage" jobs maintained', 'cnpi_type' => 'count_of_change', 'sort_order' => 4],
            ['domain' => 'employment', 'indicator_code' => 'CNPI-1e', 'name' => 'Number of jobs created with a benefit package', 'cnpi_type' => 'count_of_change', 'sort_order' => 5],
            ['domain' => 'employment', 'indicator_code' => 'CNPI-1f', 'name' => 'Percent decrease of the unemployment rate', 'cnpi_type' => 'rate_of_change', 'sort_order' => 6],
            ['domain' => 'employment', 'indicator_code' => 'CNPI-1g', 'name' => 'Percent decrease of the youth unemployment rate', 'cnpi_type' => 'rate_of_change', 'sort_order' => 7],
            ['domain' => 'employment', 'indicator_code' => 'CNPI-1h', 'name' => 'Percent decrease of the underemployment rate', 'cnpi_type' => 'rate_of_change', 'sort_order' => 8],

            // CNPI 2 — Education and Cognitive Development
            ['domain' => 'education', 'indicator_code' => 'CNPI-2a', 'name' => 'Number of early childhood/pre-school education assets added', 'cnpi_type' => 'count_of_change', 'sort_order' => 10],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2b', 'name' => 'Number of affordable child care facilities added', 'cnpi_type' => 'count_of_change', 'sort_order' => 11],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2c', 'name' => 'Number of new Early Childhood Screenings offered', 'cnpi_type' => 'count_of_change', 'sort_order' => 12],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2d', 'name' => 'Number of school age education assets added', 'cnpi_type' => 'count_of_change', 'sort_order' => 13],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2e', 'name' => 'Number of post secondary education assets added for youth', 'cnpi_type' => 'count_of_change', 'sort_order' => 14],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2f', 'name' => 'Number of basic/secondary education assets added for adults', 'cnpi_type' => 'count_of_change', 'sort_order' => 15],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2g', 'name' => 'Percent increase of children who are kindergarten ready', 'cnpi_type' => 'rate_of_change', 'sort_order' => 16],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2h', 'name' => 'Percent increase of children at basic reading level', 'cnpi_type' => 'rate_of_change', 'sort_order' => 17],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2i', 'name' => 'Percent increase of children at basic math level', 'cnpi_type' => 'rate_of_change', 'sort_order' => 18],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2j', 'name' => 'Percent increase in graduation rate', 'cnpi_type' => 'rate_of_change', 'sort_order' => 19],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2k', 'name' => 'Percent increase of youth attending post-secondary education', 'cnpi_type' => 'rate_of_change', 'sort_order' => 20],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2l', 'name' => 'Percent increase of youth graduating from post-secondary', 'cnpi_type' => 'rate_of_change', 'sort_order' => 21],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2m', 'name' => 'Percent increase of adults attending post-secondary', 'cnpi_type' => 'rate_of_change', 'sort_order' => 22],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2n', 'name' => 'Percent increase of adults graduating from post-secondary', 'cnpi_type' => 'rate_of_change', 'sort_order' => 23],
            ['domain' => 'education', 'indicator_code' => 'CNPI-2o', 'name' => 'Percent increase in adult literacy rate', 'cnpi_type' => 'rate_of_change', 'sort_order' => 24],

            // CNPI 3 — Income, Infrastructure and Asset Building
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3a.1', 'name' => 'Number of new commercial assets created', 'cnpi_type' => 'count_of_change', 'sort_order' => 30],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3a.2', 'name' => 'Number of new financial assets created', 'cnpi_type' => 'count_of_change', 'sort_order' => 31],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3a.3', 'name' => 'Number of new tech/communications assets created', 'cnpi_type' => 'count_of_change', 'sort_order' => 32],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3a.4', 'name' => 'Number of new transportation assets created', 'cnpi_type' => 'count_of_change', 'sort_order' => 33],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3a.5', 'name' => 'Number of new recreational assets created', 'cnpi_type' => 'count_of_change', 'sort_order' => 34],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3a.6', 'name' => 'Number of other public assets/physical improvements created', 'cnpi_type' => 'count_of_change', 'sort_order' => 35],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3b.1', 'name' => 'Number of existing commercial assets made accessible', 'cnpi_type' => 'count_of_change', 'sort_order' => 36],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3b.2', 'name' => 'Number of existing financial assets made accessible', 'cnpi_type' => 'count_of_change', 'sort_order' => 37],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3b.3', 'name' => 'Number of existing tech/communications assets made accessible', 'cnpi_type' => 'count_of_change', 'sort_order' => 38],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3b.4', 'name' => 'Number of existing transportation assets made accessible', 'cnpi_type' => 'count_of_change', 'sort_order' => 39],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3b.5', 'name' => 'Number of existing recreational assets made accessible', 'cnpi_type' => 'count_of_change', 'sort_order' => 40],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3b.6', 'name' => 'Number of other existing public assets made accessible', 'cnpi_type' => 'count_of_change', 'sort_order' => 41],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3c', 'name' => 'Percent decrease of abandoned/neglected buildings', 'cnpi_type' => 'rate_of_change', 'sort_order' => 42],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3d', 'name' => 'Percent decrease in emergency response time', 'cnpi_type' => 'rate_of_change', 'sort_order' => 43],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3e', 'name' => 'Percent decrease of predatory lenders/lending practices', 'cnpi_type' => 'rate_of_change', 'sort_order' => 44],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3f', 'name' => 'Percent decrease of environmental threats', 'cnpi_type' => 'rate_of_change', 'sort_order' => 45],
            ['domain' => 'income_asset', 'indicator_code' => 'CNPI-3g', 'name' => 'Percent increase of transportation services', 'cnpi_type' => 'rate_of_change', 'sort_order' => 46],

            // CNPI 4 — Housing
            ['domain' => 'housing', 'indicator_code' => 'CNPI-4a', 'name' => 'Number of affordable housing units developed', 'cnpi_type' => 'count_of_change', 'sort_order' => 50],
            ['domain' => 'housing', 'indicator_code' => 'CNPI-4b', 'name' => 'Number of affordable housing units maintained/improved', 'cnpi_type' => 'count_of_change', 'sort_order' => 51],
            ['domain' => 'housing', 'indicator_code' => 'CNPI-4c', 'name' => 'Number of shelter beds created', 'cnpi_type' => 'count_of_change', 'sort_order' => 52],
            ['domain' => 'housing', 'indicator_code' => 'CNPI-4d', 'name' => 'Number of shelter beds maintained', 'cnpi_type' => 'count_of_change', 'sort_order' => 53],
            ['domain' => 'housing', 'indicator_code' => 'CNPI-4e', 'name' => 'Percent decrease in rate of homelessness', 'cnpi_type' => 'rate_of_change', 'sort_order' => 54],
            ['domain' => 'housing', 'indicator_code' => 'CNPI-4f', 'name' => 'Percent decrease in foreclosure rate', 'cnpi_type' => 'rate_of_change', 'sort_order' => 55],
            ['domain' => 'housing', 'indicator_code' => 'CNPI-4g', 'name' => 'Percent increase in rate of home ownership', 'cnpi_type' => 'rate_of_change', 'sort_order' => 56],
            ['domain' => 'housing', 'indicator_code' => 'CNPI-4h', 'name' => 'Percent increase of affordable housing', 'cnpi_type' => 'rate_of_change', 'sort_order' => 57],
            ['domain' => 'housing', 'indicator_code' => 'CNPI-4i', 'name' => 'Percent increase of shelter beds', 'cnpi_type' => 'rate_of_change', 'sort_order' => 58],

            // CNPI 5 — Health and Social/Behavioral Development
            ['domain' => 'health_social', 'indicator_code' => 'CNPI-5a', 'name' => 'Number of physical health assets/resources created', 'cnpi_type' => 'count_of_change', 'sort_order' => 60],
            ['domain' => 'health_social', 'indicator_code' => 'CNPI-5b', 'name' => 'Number of behavioral/mental health assets created', 'cnpi_type' => 'count_of_change', 'sort_order' => 61],
            ['domain' => 'health_social', 'indicator_code' => 'CNPI-5c', 'name' => 'Number of public safety assets created', 'cnpi_type' => 'count_of_change', 'sort_order' => 62],
            ['domain' => 'health_social', 'indicator_code' => 'CNPI-5d', 'name' => 'Number of healthy food resources created', 'cnpi_type' => 'count_of_change', 'sort_order' => 63],
            ['domain' => 'health_social', 'indicator_code' => 'CNPI-5e', 'name' => 'Number of activities to improve police/community relations', 'cnpi_type' => 'count_of_change', 'sort_order' => 64],
            ['domain' => 'health_social', 'indicator_code' => 'CNPI-5f', 'name' => 'Percent decrease in infant mortality rate', 'cnpi_type' => 'rate_of_change', 'sort_order' => 65],
            ['domain' => 'health_social', 'indicator_code' => 'CNPI-5g', 'name' => 'Percent decrease in childhood obesity rate', 'cnpi_type' => 'rate_of_change', 'sort_order' => 66],
            ['domain' => 'health_social', 'indicator_code' => 'CNPI-5h', 'name' => 'Percent decrease in adult obesity rate', 'cnpi_type' => 'rate_of_change', 'sort_order' => 67],
            ['domain' => 'health_social', 'indicator_code' => 'CNPI-5i', 'name' => 'Percent increase in child immunization rate', 'cnpi_type' => 'rate_of_change', 'sort_order' => 68],
            ['domain' => 'health_social', 'indicator_code' => 'CNPI-5j', 'name' => 'Percent decrease in uninsured families', 'cnpi_type' => 'rate_of_change', 'sort_order' => 69],

            // CNPI 6 — Civic Engagement and Community Involvement
            ['domain' => 'civic_engagement', 'indicator_code' => 'CNPI-6G2a', 'name' => 'Percent increase of donated time to support service delivery', 'cnpi_type' => 'rate_of_change', 'sort_order' => 70],
            ['domain' => 'civic_engagement', 'indicator_code' => 'CNPI-6G2b', 'name' => 'Percent increase of donated resources to support service delivery', 'cnpi_type' => 'rate_of_change', 'sort_order' => 71],
            ['domain' => 'civic_engagement', 'indicator_code' => 'CNPI-6G2c', 'name' => 'Percent increase of people participating in public hearings/boards', 'cnpi_type' => 'rate_of_change', 'sort_order' => 72],
            ['domain' => 'civic_engagement', 'indicator_code' => 'CNPI-6G3a', 'name' => 'Percent increase of people with low incomes who support service delivery', 'cnpi_type' => 'rate_of_change', 'sort_order' => 73],
            ['domain' => 'civic_engagement', 'indicator_code' => 'CNPI-6G3b', 'name' => 'Percent increase of people with low incomes in leadership roles', 'cnpi_type' => 'rate_of_change', 'sort_order' => 74],
        ];

        foreach ($indicators as $data) {
            CnpiIndicator::updateOrCreate(
                ['indicator_code' => $data['indicator_code']],
                $data,
            );
        }
    }
}
