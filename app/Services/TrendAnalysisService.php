<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CsbgReportSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrendAnalysisService
{
    /**
     * Compare key metrics between two fiscal years.
     */
    public function yearOverYearComparison(int $currentYear, int $previousYear): array
    {
        $currentRange = $this->dateRangeForFiscalYear($currentYear);
        $previousRange = $this->dateRangeForFiscalYear($previousYear);

        $currentClients = $this->clientsServed(...$currentRange);
        $previousClients = $this->clientsServed(...$previousRange);

        $currentServices = $this->totalServices(...$currentRange);
        $previousServices = $this->totalServices(...$previousRange);

        $currentOutcomes = $this->achievedOutcomes($currentYear);
        $previousOutcomes = $this->achievedOutcomes($previousYear);

        return [
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'clients_served' => [
                'current' => $currentClients,
                'previous' => $previousClients,
                'change' => $currentClients - $previousClients,
                'pct_change' => $previousClients > 0 ? round((($currentClients - $previousClients) / $previousClients) * 100, 1) : null,
            ],
            'services_delivered' => [
                'current' => $currentServices,
                'previous' => $previousServices,
                'change' => $currentServices - $previousServices,
                'pct_change' => $previousServices > 0 ? round((($currentServices - $previousServices) / $previousServices) * 100, 1) : null,
            ],
            'outcomes_achieved' => [
                'current' => $currentOutcomes,
                'previous' => $previousOutcomes,
                'change' => $currentOutcomes - $previousOutcomes,
                'pct_change' => $previousOutcomes > 0 ? round((($currentOutcomes - $previousOutcomes) / $previousOutcomes) * 100, 1) : null,
            ],
        ];
    }

    /**
     * FNPI targets vs actuals for a fiscal year.
     */
    public function targetsVsActuals(int $fiscalYear): Collection
    {
        $dateRange = $this->dateRangeForFiscalYear($fiscalYear);

        $rows = DB::table('npi_indicators as ni')
            ->join('npi_goals as ng', 'ng.id', '=', 'ni.npi_goal_id')
            ->leftJoin('fnpi_targets as ft', function ($join) use ($fiscalYear) {
                $join->on('ft.npi_indicator_id', '=', 'ni.id')
                    ->where('ft.fiscal_year', $fiscalYear);
            })
            ->select([
                'ni.id as indicator_id',
                'ni.indicator_code',
                'ni.name as indicator_name',
                'ng.goal_number',
                'ng.name as goal_name',
                'ft.target_count',
            ])
            ->orderBy('ng.goal_number')
            ->orderBy('ni.indicator_code')
            ->get();

        // Get outcome counts
        $indicatorIds = $rows->pluck('indicator_id')->toArray();
        $npiService = new NpiReportService();
        $outcomeCounts = $npiService->outcomeCountsByIndicator($dateRange[0], $dateRange[1], $indicatorIds);

        // Get served counts
        $servedCounts = $this->servedByIndicator($dateRange[0], $dateRange[1], $indicatorIds);

        return $rows->map(function ($row) use ($outcomeCounts, $servedCounts) {
            $target = (int) ($row->target_count ?? 0);
            $actual = $outcomeCounts[$row->indicator_id] ?? 0;
            $served = $servedCounts[$row->indicator_id] ?? 0;
            $pctOfTarget = $target > 0 ? round(($actual / $target) * 100, 1) : 0;

            return [
                'indicator_code' => $row->indicator_code,
                'indicator_name' => $row->indicator_name,
                'goal_number' => $row->goal_number,
                'goal_name' => $row->goal_name,
                'served' => $served,
                'target' => $target,
                'actual' => $actual,
                'pct_of_target' => $pctOfTarget,
                'status' => match (true) {
                    $target === 0 => 'no_target',
                    $pctOfTarget >= 80 => 'on_track',
                    $pctOfTarget >= 50 => 'at_risk',
                    default => 'behind',
                },
            ];
        });
    }

    protected function dateRangeForFiscalYear(int $fiscalYear): array
    {
        $settings = CsbgReportSetting::first();
        $period = $settings?->reporting_period ?? 'oct_sep';

        return match ($period) {
            'oct_sep' => [($fiscalYear - 1) . '-10-01', $fiscalYear . '-09-30'],
            'jul_jun' => [($fiscalYear - 1) . '-07-01', $fiscalYear . '-06-30'],
            'jan_dec' => [$fiscalYear . '-01-01', $fiscalYear . '-12-31'],
            default => [($fiscalYear - 1) . '-10-01', $fiscalYear . '-09-30'],
        };
    }

    protected function clientsServed(string $startDate, string $endDate): int
    {
        return (int) DB::table('service_records')
            ->whereBetween('service_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->distinct()
            ->count('client_id');
    }

    protected function totalServices(string $startDate, string $endDate): int
    {
        return (int) DB::table('service_records')
            ->whereBetween('service_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->count();
    }

    protected function achievedOutcomes(int $fiscalYear): int
    {
        return (int) DB::table('outcomes')
            ->where('fiscal_year', $fiscalYear)
            ->where('status', 'achieved')
            ->whereNull('deleted_at')
            ->distinct()
            ->count('client_id');
    }

    protected function servedByIndicator(string $startDate, string $endDate, array $indicatorIds): array
    {
        if (empty($indicatorIds)) {
            return [];
        }

        return DB::table('service_records as sr')
            ->join('services as s', function ($join) {
                $join->on('s.id', '=', 'sr.service_id')->whereNull('s.deleted_at');
            })
            ->join('npi_indicator_service as nis', 'nis.service_id', '=', 's.id')
            ->whereIn('nis.npi_indicator_id', $indicatorIds)
            ->whereBetween('sr.service_date', [$startDate, $endDate])
            ->whereNull('sr.deleted_at')
            ->select('nis.npi_indicator_id', DB::raw('COUNT(DISTINCT sr.client_id) as cnt'))
            ->groupBy('nis.npi_indicator_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->npi_indicator_id => (int) $row->cnt])
            ->toArray();
    }
}
