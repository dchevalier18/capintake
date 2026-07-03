<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Program;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProgramBreakdown extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Clients Served by Program';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'year';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $startDate = match ($this->filter) {
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfYear(),
        };

        $programs = Program::active()
            ->withCount(['serviceRecords as clients_served' => function ($query) use ($startDate): void {
                $query->where('service_date', '>=', $startDate)
                    ->select(DB::raw('COUNT(DISTINCT client_id)'));
            }])
            ->orderByDesc('clients_served')
            ->get();

        $colors = [
            'rgba(245, 158, 11, 0.8)',  // amber
            'rgba(59, 130, 246, 0.8)',   // blue
            'rgba(16, 185, 129, 0.8)',   // green
            'rgba(139, 92, 246, 0.8)',   // violet
            'rgba(236, 72, 153, 0.8)',   // pink
            'rgba(249, 115, 22, 0.8)',   // orange
            'rgba(20, 184, 166, 0.8)',   // teal
            'rgba(99, 102, 241, 0.8)',   // indigo
        ];

        $borderColors = array_map(
            fn (string $c): string => str_replace('0.8)', '1)', $c),
            $colors,
        );

        return [
            'datasets' => [
                [
                    'label' => 'Clients Served',
                    'data' => $programs->pluck('clients_served')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $programs->count()),
                    'borderColor' => array_slice($borderColors, 0, $programs->count()),
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $programs->pluck('code')->toArray(),
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'month' => 'This Month',
            'quarter' => 'This Quarter',
            'year' => 'This Year',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
