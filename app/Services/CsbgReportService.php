<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgencyCapacityMetric;
use App\Models\CsbgExpenditure;
use App\Models\CsbgSrvCategory;
use App\Models\FundingSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CsbgReportService
{
    protected ?int $programId = null;

    public function forProgram(?int $programId): static
    {
        $this->programId = $programId;

        return $this;
    }

    protected function endOfDay(string $date): string
    {
        return str_contains($date, ' ') ? $date : $date . ' 23:59:59';
    }

    // -------------------------------------------------------------------------
    // Module 4 Section A — FNPI (delegates to NpiReportService)
    // -------------------------------------------------------------------------

    public function module4SectionA(string $startDate, string $endDate): Collection
    {
        return (new NpiReportService())
            ->forProgram($this->programId)
            ->generate($startDate, $endDate);
    }

    // -------------------------------------------------------------------------
    // Module 4 Section B — Services by SRV Category
    // -------------------------------------------------------------------------

    /**
     * Count unduplicated individuals and total services by SRV category.
     */
    public function module4SectionB(string $startDate, string $endDate): Collection
    {
        $query = DB::table('csbg_srv_categories as cat')
            ->leftJoin('service_srv_category as pivot', 'pivot.csbg_srv_category_id', '=', 'cat.id')
            ->leftJoin('services as s', function ($join) {
                $join->on('s.id', '=', 'pivot.service_id')->whereNull('s.deleted_at');
                if ($this->programId) {
                    $join->where('s.program_id', $this->programId);
                }
            })
            ->leftJoin('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.service_id', '=', 's.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->select([
                'cat.id',
                'cat.code',
                'cat.domain',
                'cat.group_name',
                'cat.name',
                DB::raw('COUNT(DISTINCT sr.client_id) as unduplicated_clients'),
                DB::raw('COUNT(sr.id) as total_services'),
            ])
            ->groupBy('cat.id', 'cat.code', 'cat.domain', 'cat.group_name', 'cat.name')
            ->orderBy('cat.sort_order')
            ->get();

        return $query->groupBy('domain')->map(function (Collection $categories, string $domain) {
            return [
                'domain' => $domain,
                'domain_total' => $categories->sum('unduplicated_clients'),
                'categories' => $categories->map(fn ($row) => [
                    'code' => $row->code,
                    'group_name' => $row->group_name,
                    'name' => $row->name,
                    'unduplicated_clients' => (int) $row->unduplicated_clients,
                    'total_services' => (int) $row->total_services,
                ])->values()->toArray(),
            ];
        })->values();
    }

    // -------------------------------------------------------------------------
    // Module 4 Section C — Client Characteristics (All Characteristics Report)
    // -------------------------------------------------------------------------

    /**
     * Generate unduplicated client demographics for the reporting period.
     * Only counts clients who received at least one service in the period.
     * Aligned with CSBG Annual Report v2.1 Module 4C data entry form.
     */
    public function module4SectionC(string $startDate, string $endDate): array
    {
        $clientsQuery = $this->servedClientsQuery($startDate, $endDate);

        return [
            // Section A & B totals
            'total_unduplicated_individuals' => (int) (clone $clientsQuery)->distinct()->count('c.id'),
            'total_unduplicated_households' => $this->undupHouseholdCount($startDate, $endDate),

            // C. Individual Level Characteristics
            'by_gender' => $this->breakdownBy($clientsQuery, 'c.gender'),
            'by_race' => $this->breakdownBy($clientsQuery, 'c.race'),
            'by_ethnicity' => $this->breakdownBy($clientsQuery, 'c.ethnicity'),
            'by_age' => $this->ageBreakdown($clientsQuery),
            'by_education_level' => $this->breakdownBy($clientsQuery, 'c.education_level'),
            'by_education_14_24' => $this->educationByAgeBreakdown($clientsQuery, 14, 24),
            'by_education_25_plus' => $this->educationByAgeBreakdown($clientsQuery, 25, 999),
            'by_employment_status' => $this->breakdownBy($clientsQuery, 'c.employment_status'),
            'by_health_insurance_status' => $this->breakdownBy($clientsQuery, 'c.health_insurance_status'),
            'by_health_insurance_source' => $this->breakdownBy($clientsQuery, 'c.health_insurance_source'),
            'by_military_status' => $this->breakdownBy($clientsQuery, 'c.military_status'),
            'disconnected_youth_count' => $this->disconnectedYouthCount($startDate, $endDate),

            // D. Household Level Characteristics
            'by_housing_type' => $this->housingBreakdown($startDate, $endDate),
            'by_household_type' => $this->householdTypeBreakdown($startDate, $endDate),
            'by_household_size' => $this->householdSizeBreakdown($startDate, $endDate),
            'by_fpl_bracket' => $this->fplBracketBreakdown($startDate, $endDate),
            'by_income_source_composite' => $this->incomeSourceCompositeBreakdown($startDate, $endDate),
            'by_income_source_type' => $this->incomeSourceTypeBreakdown($startDate, $endDate),
            'by_non_cash_benefit' => $this->nonCashBenefitBreakdown($startDate, $endDate),
        ];
    }

    /**
     * Base query for clients who received services in the period.
     */
    protected function servedClientsQuery(string $startDate, string $endDate)
    {
        $query = DB::table('clients as c')
            ->join('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.client_id', '=', 'c.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete');

        if ($this->programId) {
            $query->join('services as s', function ($join) {
                $join->on('s.id', '=', 'sr.service_id')
                    ->where('s.program_id', $this->programId);
            });
        }

        return $query;
    }

    /**
     * Count unduplicated clients grouped by a column value.
     */
    protected function breakdownBy($baseQuery, string $column): array
    {
        return (clone $baseQuery)
            ->select(DB::raw("{$column} as val"), DB::raw('COUNT(DISTINCT c.id) as cnt'))
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->val ?? 'unknown' => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Age range breakdown using CSBG-standard 10 brackets.
     */
    protected function ageBreakdown($baseQuery): array
    {
        $driver = config('database.default');
        $currentYear = $driver === 'sqlite'
            ? "(CAST(strftime('%Y', 'now') AS INTEGER))"
            : 'YEAR(CURDATE())';

        $ageDiff = "({$currentYear} - c.birth_year)";

        $expr = "CASE
            WHEN c.birth_year IS NULL THEN 'unknown'
            WHEN {$ageDiff} < 6 THEN '0-5'
            WHEN {$ageDiff} BETWEEN 6 AND 13 THEN '6-13'
            WHEN {$ageDiff} BETWEEN 14 AND 17 THEN '14-17'
            WHEN {$ageDiff} BETWEEN 18 AND 24 THEN '18-24'
            WHEN {$ageDiff} BETWEEN 25 AND 44 THEN '25-44'
            WHEN {$ageDiff} BETWEEN 45 AND 54 THEN '45-54'
            WHEN {$ageDiff} BETWEEN 55 AND 59 THEN '55-59'
            WHEN {$ageDiff} BETWEEN 60 AND 64 THEN '60-64'
            WHEN {$ageDiff} BETWEEN 65 AND 74 THEN '65-74'
            WHEN {$ageDiff} >= 75 THEN '75+'
            ELSE 'unknown'
        END";

        return (clone $baseQuery)
            ->select(DB::raw("{$expr} as age_range"), DB::raw('COUNT(DISTINCT c.id) as cnt'))
            ->groupBy('age_range')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->age_range => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Education level breakdown filtered by age range.
     * CSBG requires education split: ages 14-24 and ages 25+.
     */
    protected function educationByAgeBreakdown($baseQuery, int $minAge, int $maxAge): array
    {
        $driver = config('database.default');
        $currentYear = $driver === 'sqlite'
            ? "(CAST(strftime('%Y', 'now') AS INTEGER))"
            : 'YEAR(CURDATE())';

        $ageDiff = "({$currentYear} - c.birth_year)";

        return (clone $baseQuery)
            ->select(DB::raw('c.education_level as val'), DB::raw('COUNT(DISTINCT c.id) as cnt'))
            ->whereNotNull('c.birth_year')
            ->whereRaw("{$ageDiff} >= ?", [$minAge])
            ->whereRaw("{$ageDiff} <= ?", [$maxAge])
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->val ?? 'unknown' => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Unduplicated household count for served clients.
     */
    protected function undupHouseholdCount(string $startDate, string $endDate): int
    {
        return (int) DB::table('clients as c')
            ->join('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.client_id', '=', 'c.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete')
            ->whereNotNull('c.household_id')
            ->distinct()
            ->count('c.household_id');
    }

    /**
     * Housing type breakdown from households of served clients.
     */
    protected function housingBreakdown(string $startDate, string $endDate): array
    {
        $query = DB::table('clients as c')
            ->join('households as h', 'h.id', '=', 'c.household_id')
            ->join('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.client_id', '=', 'c.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete')
            ->select('h.housing_type as val', DB::raw('COUNT(DISTINCT c.id) as cnt'))
            ->groupBy('val');

        return $query->get()
            ->mapWithKeys(fn ($row) => [$row->val ?? 'unknown' => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Household type breakdown (Single Person, Single Parent Female, etc.).
     */
    protected function householdTypeBreakdown(string $startDate, string $endDate): array
    {
        return DB::table('clients as c')
            ->join('households as h', 'h.id', '=', 'c.household_id')
            ->join('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.client_id', '=', 'c.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete')
            ->select('h.household_type as val', DB::raw('COUNT(DISTINCT h.id) as cnt'))
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->val ?? 'unknown' => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Household size breakdown (1, 2, 3, 4, 5, 6+).
     */
    protected function householdSizeBreakdown(string $startDate, string $endDate): array
    {
        $sizeExpr = "CASE
            WHEN h.household_size >= 6 THEN '6+'
            ELSE CAST(h.household_size AS CHAR)
        END";

        return DB::table('clients as c')
            ->join('households as h', 'h.id', '=', 'c.household_id')
            ->join('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.client_id', '=', 'c.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete')
            ->select(DB::raw("{$sizeExpr} as size_bucket"), DB::raw('COUNT(DISTINCT h.id) as cnt'))
            ->groupBy('size_bucket')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->size_bucket => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Composite income source classification per CSBG requirements.
     * Classifies households by combination of income types:
     * employment_only, employment_and_other, other_only, no_income.
     */
    protected function incomeSourceCompositeBreakdown(string $startDate, string $endDate): array
    {
        $employmentSources = ['employment', 'self_employment'];

        // Get served household IDs
        $householdIds = DB::table('clients as c')
            ->join('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.client_id', '=', 'c.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete')
            ->whereNotNull('c.household_id')
            ->distinct()
            ->pluck('c.household_id')
            ->toArray();

        if (empty($householdIds)) {
            return [
                'employment_only' => 0,
                'employment_and_other' => 0,
                'other_only' => 0,
                'no_income' => 0,
                'unknown' => 0,
            ];
        }

        // For each household, check income record types
        $householdIncome = DB::table('income_records as ir')
            ->join('clients as c', 'c.id', '=', 'ir.client_id')
            ->whereIn('c.household_id', $householdIds)
            ->whereNull('ir.deleted_at')
            ->select(
                'c.household_id',
                DB::raw('MAX(CASE WHEN ir.source IN (\'' . implode("','", $employmentSources) . '\') THEN 1 ELSE 0 END) as has_employment'),
                DB::raw('MAX(CASE WHEN ir.source NOT IN (\'' . implode("','", $employmentSources) . '\') THEN 1 ELSE 0 END) as has_other'),
            )
            ->groupBy('c.household_id')
            ->get();

        $householdsWithIncome = $householdIncome->pluck('household_id')->toArray();
        $noIncomeCount = count(array_diff($householdIds, $householdsWithIncome));

        $result = [
            'employment_only' => 0,
            'employment_and_other' => 0,
            'other_only' => 0,
            'no_income' => $noIncomeCount,
            'unknown' => 0,
        ];

        foreach ($householdIncome as $row) {
            if ($row->has_employment && $row->has_other) {
                $result['employment_and_other']++;
            } elseif ($row->has_employment) {
                $result['employment_only']++;
            } elseif ($row->has_other) {
                $result['other_only']++;
            } else {
                $result['unknown']++;
            }
        }

        return $result;
    }

    /**
     * Income source type breakdown (count of clients by income record source).
     */
    protected function incomeSourceTypeBreakdown(string $startDate, string $endDate): array
    {
        return DB::table('clients as c')
            ->join('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.client_id', '=', 'c.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->join('income_records as ir', function ($join) {
                $join->on('ir.client_id', '=', 'c.id')
                    ->whereNull('ir.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete')
            ->select('ir.source as val', DB::raw('COUNT(DISTINCT c.id) as cnt'))
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->val ?? 'unknown' => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Non-cash benefit breakdown (count of served clients by benefit type).
     */
    protected function nonCashBenefitBreakdown(string $startDate, string $endDate): array
    {
        return DB::table('clients as c')
            ->join('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.client_id', '=', 'c.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->join('client_non_cash_benefits as ncb', function ($join) {
                $join->on('ncb.client_id', '=', 'c.id')
                    ->where('ncb.is_active', true);
            })
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete')
            ->select('ncb.benefit_type as val', DB::raw('COUNT(DISTINCT c.id) as cnt'))
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->val ?? 'unknown' => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Count of disconnected youth (ages 16-24, not working or in school).
     */
    protected function disconnectedYouthCount(string $startDate, string $endDate): int
    {
        $driver = config('database.default');
        $currentYear = $driver === 'sqlite'
            ? "(CAST(strftime('%Y', 'now') AS INTEGER))"
            : 'YEAR(CURDATE())';

        $ageDiff = "({$currentYear} - c.birth_year)";

        return (int) DB::table('clients as c')
            ->join('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.client_id', '=', 'c.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete')
            ->where('c.is_disconnected_youth', true)
            ->whereNotNull('c.birth_year')
            ->whereRaw("{$ageDiff} >= 16")
            ->whereRaw("{$ageDiff} <= 24")
            ->distinct()
            ->count('c.id');
    }

    /**
     * FPL percentage bracket breakdown based on enrollment data.
     */
    public function fplBracketBreakdown(string $startDate, string $endDate): array
    {
        $query = DB::table('clients as c')
            ->join('service_records as sr', function ($join) use ($startDate, $endDate) {
                $join->on('sr.client_id', '=', 'c.id')
                    ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
                    ->whereNull('sr.deleted_at');
            })
            ->join('enrollments as e', 'e.client_id', '=', 'c.id')
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete');

        $brackets = [
            '0-50%' => [0, 50],
            '51-75%' => [51, 75],
            '76-100%' => [76, 100],
            '101-125%' => [101, 125],
            '126-150%' => [126, 150],
            '151-175%' => [151, 175],
            '176-200%' => [176, 200],
            'over_200%' => [201, 99999],
        ];

        $result = [];
        foreach ($brackets as $label => [$min, $max]) {
            $result[$label] = (int) (clone $query)
                ->whereBetween('e.fpl_percent_at_enrollment', [$min, $max])
                ->distinct()
                ->count('c.id');
        }

        // Clients with no FPL data
        $result['unknown'] = (int) (clone $query)
            ->whereNull('e.fpl_percent_at_enrollment')
            ->distinct()
            ->count('c.id');

        return $result;
    }

    // -------------------------------------------------------------------------
    // Module 3 Section B — Community NPIs
    // -------------------------------------------------------------------------

    /**
     * Community National Performance Indicators (Counts and Rates of Change).
     */
    public function module3SectionB(int $fiscalYear): Collection
    {
        $results = DB::table('cnpi_indicators as ci')
            ->leftJoin('cnpi_results as cr', function ($join) use ($fiscalYear) {
                $join->on('cr.cnpi_indicator_id', '=', 'ci.id')
                    ->where('cr.fiscal_year', $fiscalYear);
            })
            ->select([
                'ci.id',
                'ci.domain',
                'ci.indicator_code',
                'ci.name',
                'ci.cnpi_type',
                'cr.identified_community',
                'cr.target',
                'cr.actual_result',
                'cr.performance_accuracy',
                'cr.baseline_value',
                'cr.expected_change_pct',
                'cr.actual_change_pct',
            ])
            ->orderBy('ci.sort_order')
            ->get();

        return $results->groupBy('domain')->map(function (Collection $indicators, string $domain) {
            return [
                'domain' => $domain,
                'indicators' => $indicators->map(fn ($row) => [
                    'code' => $row->indicator_code,
                    'name' => $row->name,
                    'type' => $row->cnpi_type,
                    'identified_community' => $row->identified_community,
                    'target' => $row->target ? (float) $row->target : null,
                    'actual_result' => $row->actual_result ? (float) $row->actual_result : null,
                    'performance_accuracy' => $row->performance_accuracy ? (float) $row->performance_accuracy : null,
                    'baseline_value' => $row->baseline_value ? (float) $row->baseline_value : null,
                    'expected_change_pct' => $row->expected_change_pct ? (float) $row->expected_change_pct : null,
                    'actual_change_pct' => $row->actual_change_pct ? (float) $row->actual_change_pct : null,
                ])->values()->toArray(),
            ];
        })->values();
    }

    // -------------------------------------------------------------------------
    // Module 3 Section C — Community Strategies
    // -------------------------------------------------------------------------

    /**
     * Count community initiatives using each STR strategy code.
     */
    public function module3SectionC(int $fiscalYear): Collection
    {
        return DB::table('csbg_str_categories as str')
            ->leftJoin('community_initiative_str_category as pivot', 'pivot.csbg_str_category_id', '=', 'str.id')
            ->leftJoin('community_initiatives as ci', function ($join) use ($fiscalYear) {
                $join->on('ci.id', '=', 'pivot.community_initiative_id')
                    ->where('ci.fiscal_year', $fiscalYear)
                    ->whereNull('ci.deleted_at');
            })
            ->select([
                'str.code',
                'str.group_code',
                'str.group_name',
                'str.name',
                DB::raw('COUNT(DISTINCT ci.id) as initiative_count'),
            ])
            ->groupBy('str.code', 'str.group_code', 'str.group_name', 'str.name')
            ->orderBy('str.sort_order')
            ->get()
            ->groupBy('group_code')
            ->map(function (Collection $strategies, string $groupCode) {
                $first = $strategies->first();

                return [
                    'group_code' => $groupCode,
                    'group_name' => $first->group_name,
                    'strategies' => $strategies->map(fn ($row) => [
                        'code' => $row->code,
                        'name' => $row->name,
                        'initiative_count' => (int) $row->initiative_count,
                    ])->values()->toArray(),
                ];
            })->values();
    }

    // -------------------------------------------------------------------------
    // Module 2 Section A — Expenditures
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // Module 2 Section B — Agency Capacity Building
    // -------------------------------------------------------------------------

    /**
     * Agency capacity metrics: hours, certifications, partner counts.
     */
    public function module2SectionB(int $fiscalYear): array
    {
        return AgencyCapacityMetric::forFiscalYear($fiscalYear);
    }

    // -------------------------------------------------------------------------
    // Module 2 Section C — Allocated Resources
    // -------------------------------------------------------------------------

    /**
     * Funding sources grouped by type with totals.
     */
    public function module2SectionC(int $fiscalYear): array
    {
        $sources = FundingSource::where('fiscal_year', $fiscalYear)
            ->orderBy('source_type')
            ->orderBy('source_name')
            ->get();

        $grouped = $sources->groupBy('source_type')->map(function (Collection $group, string $type) {
            return [
                'source_type' => $type,
                'type_label' => FundingSource::SOURCE_TYPES[$type] ?? $type,
                'total' => (float) $group->sum('amount'),
                'sources' => $group->map(fn ($s) => [
                    'source_name' => $s->source_name,
                    'cfda_number' => $s->cfda_number,
                    'amount' => (float) $s->amount,
                    'notes' => $s->notes,
                ])->values()->toArray(),
            ];
        })->values()->toArray();

        return [
            'by_type' => $grouped,
            'grand_total' => (float) $sources->sum('amount'),
        ];
    }

    // -------------------------------------------------------------------------
    // Module 2 Section A — Expenditures
    // -------------------------------------------------------------------------

    public function module2SectionA(int $fiscalYear): Collection
    {
        return CsbgExpenditure::where('fiscal_year', $fiscalYear)
            ->orderBy('domain')
            ->get()
            ->map(fn ($row) => [
                'domain' => $row->domain,
                'csbg_funds' => (float) $row->csbg_funds,
                'notes' => $row->notes,
            ]);
    }
}
