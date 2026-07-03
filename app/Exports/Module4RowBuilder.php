<?php

declare(strict_types=1);

namespace App\Exports;

use App\Services\CsbgReportService;
use App\Services\Lookup;

/**
 * Converts CsbgReportService output into flat, official-order rows for the
 * Module 4 export (Sections A/B/C), shaped like the NASCSP SmartForm
 * workbook agencies use to submit to their state CSBG office.
 */
class Module4RowBuilder
{
    /** Official labels for the item 13 income source composites. */
    protected const COMPOSITE_LABELS = [
        'employment_only' => 'Income from Employment Only',
        'employment_and_other' => 'Income from Employment and Other Income Source',
        'employment_other_and_noncash' => 'Income from Employment, Other Income Source, and Non-Cash Benefits',
        'employment_and_noncash' => 'Income from Employment and Non-Cash Benefits',
        'other_only' => 'Other Income Source Only',
        'other_and_noncash' => 'Other Income Source and Non-Cash Benefits',
        'no_income' => 'No Income',
        'noncash_only' => 'Non-Cash Benefits Only',
        'unknown' => 'Unknown/not reported',
    ];

    /** Official labels for the item 12 FPL bands. */
    protected const FPL_LABELS = [
        'up_to_50%' => 'Up to 50%',
        '51-75%' => '51% to 75%',
        '76-100%' => '76% to 100%',
        '101-125%' => '101% to 125%',
        '126-150%' => '126% to 150%',
        '151-175%' => '151% to 175%',
        '176-200%' => '176% to 200%',
        '201-250%' => '201% to 250%',
        '251%+' => '251% and over',
        'unknown' => 'Unknown/not reported',
    ];

    public function __construct(
        protected CsbgReportService $service,
        protected string $startDate,
        protected string $endDate,
    ) {}

    /**
     * Section A — FNPIs in the official 5-column format.
     *
     * @return array<int, array<int, string|int|float>>
     */
    public function sectionARows(): array
    {
        $rows = [];
        $rows[] = ['Indicator', 'Name', 'I. Individuals Served (#)', 'II. Target (#)', 'III. Actual Results (#)', 'IV. % Achieving Outcome', 'V. Performance Target Accuracy (%)'];

        foreach ($this->service->module4SectionA($this->startDate, $this->endDate) as $goal) {
            $rows[] = ["FNPI {$goal['goal_number']} — {$goal['goal_name']}", '', '', '', '', '', ''];

            foreach ($goal['indicators'] as $indicator) {
                $rows[] = [
                    $indicator['indicator_code'],
                    $indicator['indicator_name'],
                    $indicator['individuals_served'],
                    $indicator['target'],
                    $indicator['actual_results'],
                    $indicator['pct_achieving'],
                    $indicator['target_accuracy'],
                ];
            }
        }

        return $rows;
    }

    /**
     * Section B — services by SRV category.
     *
     * @return array<int, array<int, string|int>>
     */
    public function sectionBRows(): array
    {
        $rows = [];
        $rows[] = ['SRV Code', 'Service', 'Unduplicated Individuals Served (#)', 'Total Services (#)'];

        foreach ($this->service->module4SectionB($this->startDate, $this->endDate) as $domain) {
            $rows[] = [strtoupper(str_replace('_', ' ', $domain['domain'])), '', '', ''];

            foreach ($domain['categories'] as $category) {
                $rows[] = [
                    $category['code'],
                    $category['name'],
                    $category['unduplicated_clients'],
                    $category['total_services'],
                ];
            }
        }

        return $rows;
    }

    /**
     * Section C — the All Characteristics Report in official item order.
     *
     * @return array<int, array<int, string|int>>
     */
    public function sectionCRows(): array
    {
        $c = $this->service->module4SectionC($this->startDate, $this->endDate);
        $rows = [];

        $rows[] = ['A. Total unduplicated number of all INDIVIDUALS about whom one or more characteristics were obtained', $c['total_unduplicated_individuals']];
        $rows[] = ['B. Total unduplicated number of all HOUSEHOLDS about whom one or more characteristics were obtained', $c['total_unduplicated_households']];

        $rows[] = ['C. INDIVIDUAL LEVEL CHARACTERISTICS', ''];
        $this->appendLookupTable($rows, '1. Sex', 'gender', $c['by_gender']);
        $this->appendVerbatimTable($rows, '2. Age', $c['by_age']);
        $this->appendLookupTable($rows, '3. Education Levels (ages 14-24)', 'education_level', $c['by_education_14_24']);
        $this->appendLookupTable($rows, '3. Education Levels (ages 25+)', 'education_level', $c['by_education_25_plus']);
        $rows[] = ['4. Disconnected Youth (ages 14-24, neither working nor in school)', $c['disconnected_youth_count']];
        $this->appendVerbatimTable($rows, '5.a. Disabling Condition', $c['by_disabling_condition']);
        $this->appendLookupTable($rows, '5.b. Health Insurance', 'health_insurance_status', $c['by_health_insurance_status']);
        $this->appendLookupTable($rows, '5.c. Health Insurance Sources', 'health_insurance_source', $c['by_health_insurance_source']);
        $this->appendLookupTable($rows, '6.a. Ethnicity', 'ethnicity', $c['by_ethnicity']);
        $this->appendLookupTable($rows, '6.b. Race', 'race', $c['by_race']);
        $this->appendLookupTable($rows, '7. Military Status', 'military_status', $c['by_military_status']);
        $this->appendLookupTable($rows, '8. Work Status (Individuals 18+)', 'employment_status', $c['by_employment_status']);

        $rows[] = ['D. HOUSEHOLD LEVEL CHARACTERISTICS', ''];
        $this->appendLookupTable($rows, '9. Household Type', 'household_type', $c['by_household_type']);
        $this->appendVerbatimTable($rows, '10. Household Size', $c['by_household_size']);
        $this->appendLookupTable($rows, '11. Housing', 'housing_type', $c['by_housing_type']);
        $this->appendMappedTable($rows, '12. Level of Household Income (% of HHS Guideline)', self::FPL_LABELS, $c['by_fpl_bracket']);
        $this->appendMappedTable($rows, '13. Sources of Household Income', self::COMPOSITE_LABELS, $c['by_income_source_composite']);
        $this->appendLookupTable($rows, '14. Other Income Source', 'income_source', $c['by_income_source_type']);
        $this->appendLookupTable($rows, '15. Non-Cash Benefits', 'non_cash_benefit', $c['by_non_cash_benefit']);

        $rows[] = ['E. Unduplicated INDIVIDUALS served, by program', ''];
        foreach ($c['section_e'] as $row) {
            $rows[] = [$row['program'], $row['count']];
        }

        $rows[] = ['F. Unduplicated HOUSEHOLDS served, by program', ''];
        foreach ($c['section_f'] as $row) {
            $rows[] = [$row['program'], $row['count']];
        }

        return $rows;
    }

    /**
     * Append a table whose keys resolve through the lookup service
     * (csbg_report_code preferred, then display label).
     */
    protected function appendLookupTable(array &$rows, string $heading, string $category, array $breakdown): void
    {
        $rows[] = [$heading, ''];

        foreach ($breakdown as $key => $count) {
            $label = $key === 'unknown'
                ? 'Unknown/not reported'
                : (Lookup::csbgLabel($category, (string) $key) ?? ucfirst(str_replace('_', ' ', (string) $key)));

            $rows[] = ['  '.$label, $count];
        }

        $rows[] = ['  TOTAL', array_sum($breakdown)];
    }

    /**
     * Append a table with an explicit key => official label map.
     */
    protected function appendMappedTable(array &$rows, string $heading, array $labels, array $breakdown): void
    {
        $rows[] = [$heading, ''];

        foreach ($breakdown as $key => $count) {
            $rows[] = ['  '.($labels[$key] ?? ucfirst(str_replace('_', ' ', (string) $key))), $count];
        }

        $rows[] = ['  TOTAL', array_sum($breakdown)];
    }

    /**
     * Append a table whose keys are already display-ready (age bands, sizes).
     */
    protected function appendVerbatimTable(array &$rows, string $heading, array $breakdown): void
    {
        $rows[] = [$heading, ''];

        foreach ($breakdown as $key => $count) {
            $label = $key === 'unknown' ? 'Unknown/not reported' : ucfirst((string) $key);
            $rows[] = ['  '.$label, $count];
        }

        $rows[] = ['  TOTAL', array_sum($breakdown)];
    }
}
