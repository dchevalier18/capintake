<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\AgencyCapacityMetric;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Module 2 Section B data entry: per-fiscal-year agency capacity metrics
 * (hours of capacity building, volunteer hours, staff certifications,
 * partner organizations). A fill-in form per year, mirroring FnpiTargets.
 */
class AgencyCapacity extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Agency Capacity';

    protected static ?string $title = 'Agency Capacity (Module 2B)';

    protected string $view = 'filament.pages.agency-capacity';

    protected static string|\UnitEnum|null $navigationGroup = 'CSBG Reports';

    protected static ?int $navigationSort = 4;

    public int $fiscalYear;

    public array $metrics = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function mount(): void
    {
        $this->fiscalYear = now()->month >= 10 ? now()->year + 1 : now()->year;
        $this->loadMetrics();
    }

    public function updatedFiscalYear(): void
    {
        $this->loadMetrics();
    }

    protected function loadMetrics(): void
    {
        $existing = AgencyCapacityMetric::where('fiscal_year', $this->fiscalYear)
            ->get()
            ->keyBy('metric_type');

        $this->metrics = collect(AgencyCapacityMetric::TYPES)
            ->map(fn (string $label, string $type) => [
                'type' => $type,
                'label' => $label,
                'value' => (string) ($existing[$type]->metric_value ?? ''),
                'notes' => $existing[$type]->notes ?? '',
            ])
            ->values()
            ->toArray();
    }

    public function saveMetrics(): void
    {
        foreach ($this->metrics as $metric) {
            if ($metric['value'] === '' || $metric['value'] === null) {
                AgencyCapacityMetric::where('fiscal_year', $this->fiscalYear)
                    ->where('metric_type', $metric['type'])
                    ->delete();

                continue;
            }

            AgencyCapacityMetric::updateOrCreate(
                ['fiscal_year' => $this->fiscalYear, 'metric_type' => $metric['type'], 'metric_key' => $metric['type']],
                ['metric_value' => (float) $metric['value'], 'notes' => $metric['notes'] ?: null],
            );
        }

        Notification::make()
            ->success()
            ->title('Capacity metrics saved')
            ->body("Agency capacity metrics for FFY {$this->fiscalYear} have been saved.")
            ->send();
    }
}
