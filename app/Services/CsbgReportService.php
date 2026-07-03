<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgencyCapacityMetric;
use App\Models\CsbgExpenditure;
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
        return str_contains($date, ' ') ? $date : $date.' 23:59:59';
    }

    // -------------------------------------------------------------------------
    // Module 4 Section A — FNPI (delegates to NpiReportService)
    // -------------------------------------------------------------------------

    public function module4SectionA(string $startDate, string $endDate): Collection
    {
        return (new NpiReportService)
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
     * Generate the All Characteristics Report for the reporting period.
     *
     * Individual-level items count every individual in served households
     * (clients who received a service in the period, plus their household
     * members) — the instrument collects "data on all individuals and
     * households, whether or not funded directly by CSBG". Household-level
     * items count unduplicated households. Ages are computed as of the end
     * of the reporting period.
     *
     * Aligned with the CSBG Annual Report Module 4 Section C data entry form.
     */
    public function module4SectionC(string $startDate, string $endDate): array
    {
        $union = $this->servedIndividualsUnion($startDate, $endDate);
        $reportYear = $this->reportYear($endDate);

        return [
            // A/B. Totals
            'total_unduplicated_individuals' => (int) DB::query()->fromSub($union, 'i')->count(),
            'total_unduplicated_households' => $this->undupHouseholdCount($startDate, $endDate),

            // C. Individual Level Characteristics
            'by_gender' => $this->zeroFilled('gender', $this->individualBreakdown($union, 'gender')),
            'by_race' => $this->zeroFilled('race', $this->individualBreakdown($union, 'race')),
            'by_ethnicity' => $this->zeroFilled('ethnicity', $this->individualBreakdown($union, 'ethnicity')),
            'by_age' => $this->ageBreakdown($union, $reportYear),
            'by_education_level' => $this->zeroFilled('education_level', $this->individualBreakdown($union, 'education_level')),
            'by_education_14_24' => $this->zeroFilled('education_level', $this->educationByAgeBreakdown($union, $reportYear, 14, 24)),
            'by_education_25_plus' => $this->zeroFilled('education_level', $this->educationByAgeBreakdown($union, $reportYear, 25, 999)),
            'by_employment_status' => $this->zeroFilled('employment_status', $this->workStatusBreakdown($union, $reportYear)),
            'by_disabling_condition' => $this->disablingConditionBreakdown($union),
            'by_health_insurance_status' => $this->zeroFilled('health_insurance_status', $this->individualBreakdown($union, 'health_insurance_status')),
            'by_health_insurance_source' => $this->zeroFilled('health_insurance_source', $this->insuranceSourceBreakdown($union)),
            'by_military_status' => $this->zeroFilled('military_status', $this->individualBreakdown($union, 'military_status')),
            'disconnected_youth_count' => $this->disconnectedYouthCount($startDate, $endDate, $reportYear),

            // D. Household Level Characteristics
            'by_housing_type' => $this->zeroFilled('housing_type', $this->housingBreakdown($startDate, $endDate)),
            'by_household_type' => $this->zeroFilled('household_type', $this->householdTypeBreakdown($startDate, $endDate)),
            'by_household_size' => $this->householdSizeBreakdown($startDate, $endDate),
            'by_fpl_bracket' => $this->fplBracketBreakdown($startDate, $endDate),
            'by_income_source_composite' => $this->incomeSourceCompositeBreakdown($startDate, $endDate),
            'by_income_source_type' => $this->incomeSourceTypeBreakdown($startDate, $endDate),
            'by_non_cash_benefit' => $this->zeroFilled('non_cash_benefit', $this->nonCashBenefitBreakdown($startDate, $endDate)),

            // E/F. Unduplicated individuals and households by program
            'section_e' => $this->individualsByProgram($startDate, $endDate),
            'section_f' => $this->householdsByProgram($startDate, $endDate),
        ];
    }

    /**
     * Year used for age computations: the reporting period's end year.
     */
    protected function reportYear(string $endDate): int
    {
        return (int) substr($endDate, 0, 4);
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
     * Subquery of household ids for households with at least one served client.
     */
    protected function servedHouseholdIdsQuery(string $startDate, string $endDate)
    {
        return $this->servedClientsQuery($startDate, $endDate)
            ->whereNotNull('c.household_id')
            ->distinct()
            ->select('c.household_id');
    }

    /**
     * All individuals in served households: served clients UNION ALL the
     * members of their households. One row per person; HouseholdMember rows
     * represent people who are not themselves clients (the intake wizard
     * enforces this), so the union does not double count.
     */
    protected function servedIndividualsUnion(string $startDate, string $endDate)
    {
        $clients = $this->servedClientsQuery($startDate, $endDate)
            ->distinct()
            ->select([
                DB::raw("'client' as person_type"),
                'c.id as person_id',
                'c.household_id',
                'c.gender',
                'c.race',
                'c.ethnicity',
                'c.birth_year',
                'c.education_level',
                'c.employment_status',
                'c.health_insurance_status',
                'c.health_insurance_source',
                'c.military_status',
                'c.is_disabled',
            ]);

        $members = DB::table('household_members as m')
            ->whereIn('m.household_id', $this->servedHouseholdIdsQuery($startDate, $endDate))
            ->whereNull('m.deleted_at')
            ->select([
                DB::raw("'member' as person_type"),
                'm.id as person_id',
                'm.household_id',
                'm.gender',
                'm.race',
                'm.ethnicity',
                'm.birth_year',
                'm.education_level',
                'm.employment_status',
                'm.health_insurance_status',
                'm.health_insurance_source',
                'm.military_status',
                'm.is_disabled',
            ]);

        return $clients->unionAll($members);
    }

    /**
     * Count individuals (clients + household members) grouped by a column.
     */
    protected function individualBreakdown($union, string $column): array
    {
        return DB::query()->fromSub($union, 'i')
            ->select(DB::raw("i.{$column} as val"), DB::raw('COUNT(*) as cnt'))
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->val === null || $row->val === '') ? 'unknown' : $row->val => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Age range breakdown using the CSBG item 2 brackets, as of the
     * reporting period end year.
     */
    protected function ageBreakdown($union, int $reportYear): array
    {
        $ageDiff = "({$reportYear} - i.birth_year)";

        $expr = "CASE
            WHEN i.birth_year IS NULL THEN 'unknown'
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

        $counts = DB::query()->fromSub($union, 'i')
            ->select(DB::raw("{$expr} as age_range"), DB::raw('COUNT(*) as cnt'))
            ->groupBy('age_range')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->age_range => (int) $row->cnt])
            ->toArray();

        $ordered = [];
        foreach (['0-5', '6-13', '14-17', '18-24', '25-44', '45-54', '55-59', '60-64', '65-74', '75+', 'unknown'] as $bracket) {
            $ordered[$bracket] = $counts[$bracket] ?? 0;
        }

        return $ordered;
    }

    /**
     * Education level breakdown filtered by age range (as of period end).
     * CSBG requires the education split: ages 14-24 and ages 25+.
     */
    protected function educationByAgeBreakdown($union, int $reportYear, int $minAge, int $maxAge): array
    {
        $ageDiff = "({$reportYear} - i.birth_year)";

        return DB::query()->fromSub($union, 'i')
            ->select(DB::raw('i.education_level as val'), DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('i.birth_year')
            ->whereRaw("{$ageDiff} >= ?", [$minAge])
            ->whereRaw("{$ageDiff} <= ?", [$maxAge])
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->val === null || $row->val === '') ? 'unknown' : $row->val => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Work status (item 8) counts individuals 18+ only, per the instrument.
     */
    protected function workStatusBreakdown($union, int $reportYear): array
    {
        $ageDiff = "({$reportYear} - i.birth_year)";

        return DB::query()->fromSub($union, 'i')
            ->select(DB::raw('i.employment_status as val'), DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('i.birth_year')
            ->whereRaw("{$ageDiff} >= 18")
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->val === null || $row->val === '') ? 'unknown' : $row->val => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Disabling condition (item 5a): yes / no counts from the is_disabled flag.
     */
    protected function disablingConditionBreakdown($union): array
    {
        $counts = DB::query()->fromSub($union, 'i')
            ->select('i.is_disabled', DB::raw('COUNT(*) as cnt'))
            ->groupBy('i.is_disabled')
            ->get();

        $result = ['yes' => 0, 'no' => 0, 'unknown' => 0];
        foreach ($counts as $row) {
            if ($row->is_disabled === null) {
                $result['unknown'] += (int) $row->cnt;
            } elseif ((bool) $row->is_disabled) {
                $result['yes'] += (int) $row->cnt;
            } else {
                $result['no'] += (int) $row->cnt;
            }
        }

        return $result;
    }

    /**
     * Health insurance sources (item 5c) — only individuals who reported
     * having insurance.
     */
    protected function insuranceSourceBreakdown($union): array
    {
        return DB::query()->fromSub($union, 'i')
            ->where('i.health_insurance_status', 'yes')
            ->select(DB::raw('i.health_insurance_source as val'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->val === null || $row->val === '') ? 'unknown' : $row->val => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Zero-fill a breakdown so every official lookup value appears (in
     * seeded sort order) even when its count is zero, with "unknown" last.
     */
    protected function zeroFilled(string $categoryKey, array $counts): array
    {
        $result = [];
        foreach (Lookup::allValues($categoryKey)->pluck('key') as $key) {
            if ($key === 'unknown') {
                continue;
            }
            $result[$key] = $counts[$key] ?? 0;
        }

        // Preserve legacy/custom stored values not present in the lookup
        foreach ($counts as $key => $count) {
            if ($key !== 'unknown' && ! array_key_exists($key, $result)) {
                $result[$key] = $count;
            }
        }

        $result['unknown'] = $counts['unknown'] ?? 0;

        return $result;
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
     * Housing tenure breakdown (item 11), counted in households.
     */
    protected function housingBreakdown(string $startDate, string $endDate): array
    {
        return DB::table('households as h')
            ->whereIn('h.id', $this->servedHouseholdIdsQuery($startDate, $endDate))
            ->whereNull('h.deleted_at')
            ->select('h.housing_type as val', DB::raw('COUNT(DISTINCT h.id) as cnt'))
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->val === null || $row->val === '') ? 'unknown' : $row->val => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Household type breakdown (item 9), counted in households.
     */
    protected function householdTypeBreakdown(string $startDate, string $endDate): array
    {
        return DB::table('households as h')
            ->whereIn('h.id', $this->servedHouseholdIdsQuery($startDate, $endDate))
            ->whereNull('h.deleted_at')
            ->select('h.household_type as val', DB::raw('COUNT(DISTINCT h.id) as cnt'))
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->val === null || $row->val === '') ? 'unknown' : $row->val => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Household size breakdown (item 10: 1, 2, 3, 4, 5, 6+).
     */
    protected function householdSizeBreakdown(string $startDate, string $endDate): array
    {
        $sizeExpr = "CASE
            WHEN h.household_size IS NULL THEN 'unknown'
            WHEN h.household_size >= 6 THEN '6+'
            ELSE CAST(h.household_size AS CHAR)
        END";

        $counts = DB::table('households as h')
            ->whereIn('h.id', $this->servedHouseholdIdsQuery($startDate, $endDate))
            ->whereNull('h.deleted_at')
            ->select(DB::raw("{$sizeExpr} as size_bucket"), DB::raw('COUNT(DISTINCT h.id) as cnt'))
            ->groupBy('size_bucket')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->size_bucket => (int) $row->cnt])
            ->toArray();

        $ordered = [];
        foreach (['1', '2', '3', '4', '5', '6+', 'unknown'] as $bucket) {
            $ordered[$bucket] = $counts[$bucket] ?? 0;
        }

        return $ordered;
    }

    /**
     * Sources of Household Income (item 13): classify each served household
     * by its combination of employment income, other income, and non-cash
     * benefits — the nine official composite categories.
     *
     * Households with no income records and no benefit records fall into
     * "unknown" rather than "no_income": asserting No Income requires an
     * affirmative declaration the system does not capture; the Data Quality
     * dashboard drives completion instead.
     */
    protected function incomeSourceCompositeBreakdown(string $startDate, string $endDate): array
    {
        $result = [
            'employment_only' => 0,
            'employment_and_other' => 0,
            'employment_other_and_noncash' => 0,
            'employment_and_noncash' => 0,
            'other_only' => 0,
            'other_and_noncash' => 0,
            'no_income' => 0,
            'noncash_only' => 0,
            'unknown' => 0,
        ];

        $householdIds = $this->servedHouseholdIdsQuery($startDate, $endDate)
            ->pluck('c.household_id')
            ->toArray();

        if (empty($householdIds)) {
            return $result;
        }

        $employmentSources = ['employment', 'self_employment'];
        $householdIdExpr = 'COALESCE(c.household_id, m.household_id)';

        // Income booleans per household, including household member income
        $householdIncome = DB::table('income_records as ir')
            ->leftJoin('clients as c', 'c.id', '=', 'ir.client_id')
            ->leftJoin('household_members as m', 'm.id', '=', 'ir.household_member_id')
            ->whereNull('ir.deleted_at')
            ->whereIn(DB::raw($householdIdExpr), $householdIds)
            ->select(
                DB::raw("{$householdIdExpr} as household_id"),
                DB::raw('MAX(CASE WHEN ir.source IN (\''.implode("','", $employmentSources).'\') THEN 1 ELSE 0 END) as has_employment'),
                DB::raw('MAX(CASE WHEN ir.source NOT IN (\''.implode("','", $employmentSources).'\') THEN 1 ELSE 0 END) as has_other'),
            )
            ->groupBy(DB::raw($householdIdExpr))
            ->get()
            ->keyBy('household_id');

        // Households with an active non-cash benefit
        $noncashHouseholds = DB::table('client_non_cash_benefits as ncb')
            ->join('clients as c', 'c.id', '=', 'ncb.client_id')
            ->where('ncb.is_active', true)
            ->whereNull('c.deleted_at')
            ->whereIn('c.household_id', $householdIds)
            ->distinct()
            ->pluck('c.household_id')
            ->flip();

        foreach ($householdIds as $householdId) {
            $income = $householdIncome->get($householdId);
            $hasEmployment = (bool) ($income->has_employment ?? false);
            $hasOther = (bool) ($income->has_other ?? false);
            $hasNoncash = isset($noncashHouseholds[$householdId]);

            $category = match (true) {
                $hasEmployment && $hasOther && $hasNoncash => 'employment_other_and_noncash',
                $hasEmployment && $hasOther => 'employment_and_other',
                $hasEmployment && $hasNoncash => 'employment_and_noncash',
                $hasEmployment => 'employment_only',
                $hasOther && $hasNoncash => 'other_and_noncash',
                $hasOther => 'other_only',
                $hasNoncash => 'noncash_only',
                default => 'unknown',
            };

            $result[$category]++;
        }

        return $result;
    }

    /**
     * Other Income Source breakdown (item 14): households reporting each
     * non-employment income source, including household member income.
     */
    protected function incomeSourceTypeBreakdown(string $startDate, string $endDate): array
    {
        $householdIds = $this->servedHouseholdIdsQuery($startDate, $endDate)
            ->pluck('c.household_id')
            ->toArray();

        if (empty($householdIds)) {
            return $this->zeroFilledOtherIncome([]);
        }

        $householdIdExpr = 'COALESCE(c.household_id, m.household_id)';

        $counts = DB::table('income_records as ir')
            ->leftJoin('clients as c', 'c.id', '=', 'ir.client_id')
            ->leftJoin('household_members as m', 'm.id', '=', 'ir.household_member_id')
            ->whereNull('ir.deleted_at')
            ->whereNotIn('ir.source', ['employment', 'self_employment'])
            ->whereIn(DB::raw($householdIdExpr), $householdIds)
            ->select('ir.source as val', DB::raw("COUNT(DISTINCT {$householdIdExpr}) as cnt"))
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->val === null || $row->val === '') ? 'unknown' : $row->val => (int) $row->cnt])
            ->toArray();

        return $this->zeroFilledOtherIncome($counts);
    }

    /**
     * Zero-fill item 14 against the income_source lookup, excluding the
     * employment sources (the official list is "Other Income Source" only).
     */
    protected function zeroFilledOtherIncome(array $counts): array
    {
        $filled = $this->zeroFilled('income_source', $counts);
        unset($filled['employment'], $filled['self_employment']);

        return $filled;
    }

    /**
     * Non-Cash Benefits breakdown (item 15), counted in households.
     */
    protected function nonCashBenefitBreakdown(string $startDate, string $endDate): array
    {
        return DB::table('client_non_cash_benefits as ncb')
            ->join('clients as c', 'c.id', '=', 'ncb.client_id')
            ->where('ncb.is_active', true)
            ->whereNull('c.deleted_at')
            ->whereNotNull('c.household_id')
            ->whereIn('c.household_id', $this->servedHouseholdIdsQuery($startDate, $endDate))
            ->select('ncb.benefit_type as val', DB::raw('COUNT(DISTINCT c.household_id) as cnt'))
            ->groupBy('val')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->val === null || $row->val === '') ? 'unknown' : $row->val => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Section E: unduplicated individuals served per program.
     *
     * @return array<int, array{program: string, count: int}>
     */
    protected function individualsByProgram(string $startDate, string $endDate): array
    {
        return DB::table('service_records as sr')
            ->join('services as s', function ($join) {
                $join->on('s.id', '=', 'sr.service_id')->whereNull('s.deleted_at');
            })
            ->join('programs as p', 'p.id', '=', 's.program_id')
            ->join('clients as c', 'c.id', '=', 'sr.client_id')
            ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
            ->whereNull('sr.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete')
            ->when($this->programId, fn ($q) => $q->where('p.id', $this->programId))
            ->select('p.name as program', DB::raw('COUNT(DISTINCT c.id) as cnt'))
            ->groupBy('p.id', 'p.name')
            ->orderBy('p.name')
            ->get()
            ->map(fn ($row) => ['program' => $row->program, 'count' => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Section F: unduplicated households served per program.
     *
     * @return array<int, array{program: string, count: int}>
     */
    protected function householdsByProgram(string $startDate, string $endDate): array
    {
        return DB::table('service_records as sr')
            ->join('services as s', function ($join) {
                $join->on('s.id', '=', 'sr.service_id')->whereNull('s.deleted_at');
            })
            ->join('programs as p', 'p.id', '=', 's.program_id')
            ->join('clients as c', 'c.id', '=', 'sr.client_id')
            ->whereBetween('sr.service_date', [$startDate, $this->endOfDay($endDate)])
            ->whereNull('sr.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('c.intake_status', 'complete')
            ->whereNotNull('c.household_id')
            ->when($this->programId, fn ($q) => $q->where('p.id', $this->programId))
            ->select('p.name as program', DB::raw('COUNT(DISTINCT c.household_id) as cnt'))
            ->groupBy('p.id', 'p.name')
            ->orderBy('p.name')
            ->get()
            ->map(fn ($row) => ['program' => $row->program, 'count' => (int) $row->cnt])
            ->toArray();
    }

    /**
     * Count of disconnected youth (item 4: ages 14-24, neither working nor
     * in school), as of the reporting period end year.
     */
    protected function disconnectedYouthCount(string $startDate, string $endDate, ?int $reportYear = null): int
    {
        $reportYear ??= $this->reportYear($endDate);
        $ageDiff = "({$reportYear} - c.birth_year)";

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
            ->whereRaw("{$ageDiff} >= 14")
            ->whereRaw("{$ageDiff} <= 24")
            ->distinct()
            ->count('c.id');
    }

    /**
     * Level of Household Income as % of the HHS poverty guideline (item 12):
     * the nine official bands plus unknown, counted in HOUSEHOLDS. Each
     * household is classified by its most recent enrollment snapshot
     * (fpl_percent_at_enrollment) on or before the period end.
     */
    public function fplBracketBreakdown(string $startDate, string $endDate): array
    {
        $brackets = [
            'up_to_50%' => [0, 50],
            '51-75%' => [51, 75],
            '76-100%' => [76, 100],
            '101-125%' => [101, 125],
            '126-150%' => [126, 150],
            '151-175%' => [151, 175],
            '176-200%' => [176, 200],
            '201-250%' => [201, 250],
            '251%+' => [251, PHP_INT_MAX],
        ];

        $result = array_fill_keys([...array_keys($brackets), 'unknown'], 0);

        $householdIds = $this->servedHouseholdIdsQuery($startDate, $endDate)
            ->pluck('c.household_id')
            ->toArray();

        if (empty($householdIds)) {
            return $result;
        }

        // Latest enrollment snapshot per household on or before period end;
        // if a household's only snapshots postdate the period (backdated
        // service entry), fall back to its earliest snapshot rather than
        // reporting the household as unknown.
        $enrollments = DB::table('enrollments as e')
            ->join('clients as c', 'c.id', '=', 'e.client_id')
            ->whereIn('c.household_id', $householdIds)
            ->whereNull('e.deleted_at')
            ->whereNull('c.deleted_at')
            ->select('c.household_id', 'e.enrolled_at', 'e.id', 'e.fpl_percent_at_enrollment')
            ->get()
            ->groupBy('household_id');

        $periodEnd = $this->endOfDay($endDate);

        $snapshotByHousehold = $enrollments->map(function (Collection $rows) use ($periodEnd) {
            $inPeriod = $rows->filter(fn ($row) => (string) $row->enrolled_at <= $periodEnd);

            if ($inPeriod->isNotEmpty()) {
                return $inPeriod->sortBy([['enrolled_at', 'desc'], ['id', 'desc']])->first();
            }

            return $rows->sortBy([['enrolled_at', 'asc'], ['id', 'asc']])->first();
        });

        foreach ($householdIds as $householdId) {
            $fpl = $snapshotByHousehold->get($householdId)?->fpl_percent_at_enrollment;

            if ($fpl === null) {
                $result['unknown']++;

                continue;
            }

            $fpl = (float) $fpl;
            foreach ($brackets as $label => [$min, $max]) {
                if ($fpl >= $min && $fpl <= $max) {
                    $result[$label]++;

                    break;
                }
            }
        }

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
