<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NpiReportService
{
    protected ?int $programId = null;

    public function forProgram(?int $programId): static
    {
        $this->programId = $programId;

        return $this;
    }

    /**
     * Generate the full NPI report for a date range.
     *
     * Returns a collection of goals, each with indicators, counts, and demographics.
     */
    public function generate(string $startDate, string $endDate): Collection
    {
        $rows = $this->baseQuery($startDate, $endDate)
            ->select([
                'ng.id as goal_id',
                'ng.goal_number',
                'ng.name as goal_name',
                'ni.id as indicator_id',
                'ni.indicator_code',
                'ni.name as indicator_name',
                DB::raw('COUNT(DISTINCT sr.client_id) as unduplicated_clients'),
                DB::raw('COUNT(sr.id) as total_services'),
                DB::raw('COALESCE(SUM(sr.value), 0) as total_value'),
            ])
            ->groupBy('ng.id', 'ng.goal_number', 'ng.name', 'ni.id', 'ni.indicator_code', 'ni.name')
            ->orderBy('ng.goal_number')
            ->orderBy('ni.indicator_code')
            ->get();

        // Pre-fetch all demographic data in two bulk queries
        $indicatorIds = $rows->pluck('indicator_id')->unique()->toArray();
        $raceGender = $this->demographicsByIndicator($startDate, $endDate, $indicatorIds);

        return $rows->groupBy('goal_id')->map(function (Collection $indicators) use ($startDate, $endDate, $raceGender): array {
            $first = $indicators->first();

            $goalClientCount = $this->goalUnduplicatedCount(
                (int) $first->goal_id,
                $startDate,
                $endDate,
            );

            return [
                'goal_number' => $first->goal_number,
                'goal_name' => $first->goal_name,
                'goal_total_clients' => $goalClientCount,
                'indicators' => $indicators->map(function ($row) use ($raceGender): array {
                    $id = $row->indicator_id;

                    return [
                        'indicator_code' => $row->indicator_code,
                        'indicator_name' => $row->indicator_name,
                        'unduplicated_clients' => (int) $row->unduplicated_clients,
                        'total_services' => (int) $row->total_services,
                        'total_value' => (float) $row->total_value,
                        'by_race' => $raceGender['race'][$id] ?? [],
                        'by_gender' => $raceGender['gender'][$id] ?? [],
                        'by_age' => $raceGender['age'][$id] ?? [],
                    ];
                })->values()->toArray(),
            ];
        })->values();
    }

    /**
     * Build the base query joining goals → indicators → pivot → services → records.
     */
    protected function baseQuery(string $startDate, string $endDate)
    {
        $programId = $this->programId;

        $query = DB::table('npi_goals as ng')
            ->join('npi_indicators as ni', 'ni.npi_goal_id', '=', 'ng.id')
            ->leftJoin('npi_indicator_service as nis', 'nis.npi_indicator_id', '=', 'ni.id')
            ->leftJoin('services as s', function ($join) use ($programId): void {
                $join->on('s.id', '=', 'nis.service_id')
                    ->whereNull('s.deleted_at');
                if ($programId) {
                    $join->where('s.program_id', $programId);
                }
            })
            ->leftJoin('service_records as sr', function ($join) use ($startDate, $endDate): void {
                $join->on('sr.service_id', '=', 's.id')
                    ->whereBetween('sr.service_date', [$startDate, $endDate])
                    ->whereNull('sr.deleted_at');
            });

        return $query;
    }

    /**
     * Bulk-fetch demographic breakdowns for a set of indicator IDs.
     *
     * Returns ['race' => [indicator_id => [...]], 'gender' => [...], 'age' => [...]]
     */
    protected function demographicsByIndicator(string $startDate, string $endDate, array $indicatorIds): array
    {
        if (empty($indicatorIds)) {
            return ['race' => [], 'gender' => [], 'age' => []];
        }

        $baseJoin = fn () => DB::table('service_records as sr')
            ->join('clients as c', 'c.id', '=', 'sr.client_id')
            ->join('services as s', function ($join): void {
                $join->on('s.id', '=', 'sr.service_id')->whereNull('s.deleted_at');
            })
            ->join('npi_indicator_service as nis', 'nis.service_id', '=', 's.id')
            ->whereIn('nis.npi_indicator_id', $indicatorIds)
            ->whereBetween('sr.service_date', [$startDate, $endDate])
            ->whereNull('sr.deleted_at')
            ->when($this->programId, fn ($q) => $q->where('s.program_id', $this->programId));

        // Race breakdown
        $raceRows = $baseJoin()
            ->select('nis.npi_indicator_id', 'c.race', DB::raw('COUNT(DISTINCT sr.client_id) as cnt'))
            ->groupBy('nis.npi_indicator_id', 'c.race')
            ->get();

        $byRace = [];
        foreach ($raceRows as $row) {
            $byRace[$row->npi_indicator_id][$row->race ?? 'unknown'] = (int) $row->cnt;
        }

        // Gender breakdown
        $genderRows = $baseJoin()
            ->select('nis.npi_indicator_id', 'c.gender', DB::raw('COUNT(DISTINCT sr.client_id) as cnt'))
            ->groupBy('nis.npi_indicator_id', 'c.gender')
            ->get();

        $byGender = [];
        foreach ($genderRows as $row) {
            $byGender[$row->npi_indicator_id][$row->gender ?? 'unknown'] = (int) $row->cnt;
        }

        // Age range breakdown
        $ageRows = $baseJoin()
            ->select(
                'nis.npi_indicator_id',
                DB::raw($this->ageRangeExpression()),
                DB::raw('COUNT(DISTINCT sr.client_id) as cnt'),
            )
            ->groupBy('nis.npi_indicator_id', 'age_range')
            ->get();

        $byAge = [];
        foreach ($ageRows as $row) {
            $byAge[$row->npi_indicator_id][$row->age_range ?? 'unknown'] = (int) $row->cnt;
        }

        return ['race' => $byRace, 'gender' => $byGender, 'age' => $byAge];
    }

    /**
     * SQL expression to bucket client ages into CSBG-standard ranges.
     */
    protected function ageRangeExpression(): string
    {
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $ageDiff = "(strftime('%Y', 'now') - strftime('%Y', c.date_of_birth))";
        } else {
            $ageDiff = 'TIMESTAMPDIFF(YEAR, c.date_of_birth, CURDATE())';
        }

        return "CASE
            WHEN {$ageDiff} < 6 THEN '0-5'
            WHEN {$ageDiff} BETWEEN 6 AND 12 THEN '6-12'
            WHEN {$ageDiff} BETWEEN 13 AND 17 THEN '13-17'
            WHEN {$ageDiff} BETWEEN 18 AND 24 THEN '18-24'
            WHEN {$ageDiff} BETWEEN 25 AND 44 THEN '25-44'
            WHEN {$ageDiff} BETWEEN 45 AND 54 THEN '45-54'
            WHEN {$ageDiff} BETWEEN 55 AND 64 THEN '55-64'
            WHEN {$ageDiff} >= 65 THEN '65+'
            ELSE 'unknown'
        END as age_range";
    }

    /**
     * Unduplicated client count for an entire goal (across all its indicators).
     */
    public function goalUnduplicatedCount(int $goalId, string $startDate, string $endDate): int
    {
        $query = DB::table('service_records as sr')
            ->join('services as s', function ($join): void {
                $join->on('s.id', '=', 'sr.service_id')->whereNull('s.deleted_at');
            })
            ->join('npi_indicator_service as nis', 'nis.service_id', '=', 's.id')
            ->join('npi_indicators as ni', function ($join) use ($goalId): void {
                $join->on('ni.id', '=', 'nis.npi_indicator_id')
                    ->where('ni.npi_goal_id', '=', $goalId);
            })
            ->whereBetween('sr.service_date', [$startDate, $endDate])
            ->whereNull('sr.deleted_at');

        if ($this->programId) {
            $query->where('s.program_id', $this->programId);
        }

        return (int) $query->distinct()->count('sr.client_id');
    }

    /**
     * Grand total: unduplicated clients across ALL NPI indicators.
     */
    public function grandTotalUnduplicatedClients(string $startDate, string $endDate): int
    {
        $query = DB::table('service_records as sr')
            ->join('services as s', function ($join): void {
                $join->on('s.id', '=', 'sr.service_id')->whereNull('s.deleted_at');
            })
            ->join('npi_indicator_service as nis', 'nis.service_id', '=', 's.id')
            ->whereBetween('sr.service_date', [$startDate, $endDate])
            ->whereNull('sr.deleted_at');

        if ($this->programId) {
            $query->where('s.program_id', $this->programId);
        }

        return (int) $query->distinct()->count('sr.client_id');
    }

    /**
     * Flatten the report into rows for CSV export (with demographic columns).
     */
    public function toFlatRows(string $startDate, string $endDate): array
    {
        $report = $this->generate($startDate, $endDate);
        $rows = [];

        $races = ['white', 'black', 'asian', 'native_american', 'pacific_islander', 'multi_racial', 'other'];
        $genders = ['male', 'female', 'non_binary', 'other'];
        $ages = ['0-5', '6-12', '13-17', '18-24', '25-44', '45-54', '55-64', '65+'];

        // Header
        $header = ['NPI Code', 'Goal / Indicator', 'Unduplicated Individuals', 'Total Services', 'Total Value ($)'];
        foreach ($races as $r) {
            $header[] = 'Race: ' . ucfirst(str_replace('_', ' ', $r));
        }
        foreach ($genders as $g) {
            $header[] = 'Gender: ' . ucfirst(str_replace('_', ' ', $g));
        }
        foreach ($ages as $a) {
            $header[] = 'Age: ' . $a;
        }
        $rows[] = $header;

        foreach ($report as $goal) {
            $goalRow = ['Goal ' . $goal['goal_number'], $goal['goal_name'], $goal['goal_total_clients'], '', ''];
            $goalRow = array_merge($goalRow, array_fill(0, count($races) + count($genders) + count($ages), ''));
            $rows[] = $goalRow;

            foreach ($goal['indicators'] as $indicator) {
                $row = [
                    $indicator['indicator_code'],
                    $indicator['indicator_name'],
                    $indicator['unduplicated_clients'],
                    $indicator['total_services'],
                    number_format($indicator['total_value'], 2),
                ];

                foreach ($races as $r) {
                    $row[] = $indicator['by_race'][$r] ?? 0;
                }
                foreach ($genders as $g) {
                    $row[] = $indicator['by_gender'][$g] ?? 0;
                }
                foreach ($ages as $a) {
                    $row[] = $indicator['by_age'][$a] ?? 0;
                }

                $rows[] = $row;
            }
        }

        $grandTotalRow = ['', 'GRAND TOTAL (Unduplicated)', $this->grandTotalUnduplicatedClients($startDate, $endDate), '', ''];
        $grandTotalRow = array_merge($grandTotalRow, array_fill(0, count($races) + count($genders) + count($ages), ''));
        $rows[] = $grandTotalRow;

        return $rows;
    }
}
