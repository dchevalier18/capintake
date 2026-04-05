<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EnrollmentStatus;
use App\Enums\IntakeStatus;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Program;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();
        $startOfWeek = $now->copy()->startOfWeek();

        // Clients served this month (had a service record this month)
        $clientsThisMonth = Client::complete()
            ->whereHas('serviceRecords', fn ($q) => $q->where('service_date', '>=', $startOfMonth))
            ->count();

        // Clients served this year
        $clientsThisYear = Client::complete()
            ->whereHas('serviceRecords', fn ($q) => $q->where('service_date', '>=', $startOfYear))
            ->count();

        // New intakes this week
        $intakesThisWeek = Client::complete()
            ->where('created_at', '>=', $startOfWeek)
            ->count();

        $intakesLastWeek = Client::complete()
            ->whereBetween('created_at', [
                $startOfWeek->copy()->subWeek(),
                $startOfWeek,
            ])
            ->count();

        $intakeTrend = $intakesLastWeek > 0
            ? round((($intakesThisWeek - $intakesLastWeek) / $intakesLastWeek) * 100)
            : null;

        // Active enrollments
        $activeEnrollments = Enrollment::where('status', EnrollmentStatus::Active)->count();

        // Unduplicated client count (unique clients with at least one service this year)
        $unduplicatedClients = Client::complete()
            ->whereHas('serviceRecords', fn ($q) => $q->where('service_date', '>=', $startOfYear))
            ->distinct()
            ->count('id');

        return [
            Stat::make('Clients Served', $clientsThisMonth)
                ->description($clientsThisYear . ' this year')
                ->descriptionIcon('heroicon-m-calendar')
                ->descriptionColor('gray')
                ->color('primary')
                ->chart($this->getMonthlyClientTrend()),

            Stat::make('New Intakes This Week', $intakesThisWeek)
                ->description(
                    $intakeTrend === null
                        ? ($intakesThisWeek > 0 ? $intakesThisWeek . ' new this week' : 'No intakes yet')
                        : ($intakeTrend >= 0 ? $intakeTrend . '% increase' : abs($intakeTrend) . '% decrease')
                )
                ->descriptionIcon(
                    $intakeTrend === null
                        ? 'heroicon-m-information-circle'
                        : ($intakeTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                )
                ->descriptionColor(
                    $intakeTrend === null ? 'gray' : ($intakeTrend >= 0 ? 'success' : 'danger')
                )
                ->color('success'),

            Stat::make('Active Enrollments', $activeEnrollments)
                ->description($this->getTopProgramLabel())
                ->descriptionIcon('heroicon-m-academic-cap')
                ->descriptionColor('gray')
                ->color('warning'),

            Stat::make('Unduplicated Clients', $unduplicatedClients)
                ->description('Fiscal year to date')
                ->descriptionIcon('heroicon-m-user-group')
                ->descriptionColor('gray')
                ->color('info'),
        ];
    }

    protected function getMonthlyClientTrend(): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Client::complete()
                ->whereHas('serviceRecords', fn ($q) => $q
                    ->whereYear('service_date', $date->year)
                    ->whereMonth('service_date', $date->month)
                )
                ->count();
            $trend[] = $count;
        }

        return $trend;
    }

    protected function getTopProgramLabel(): string
    {
        $top = Program::withCount(['enrollments' => fn ($q) => $q->where('status', EnrollmentStatus::Active)])
            ->orderByDesc('enrollments_count')
            ->first();

        if (! $top || $top->enrollments_count === 0) {
            return 'No active programs';
        }

        return 'Top: ' . $top->name . ' (' . $top->enrollments_count . ')';
    }
}
