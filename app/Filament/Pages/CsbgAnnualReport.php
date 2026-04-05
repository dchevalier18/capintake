<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\AgencySetting;
use App\Models\CommunityInitiative;
use App\Models\CsbgReportSetting;
use App\Services\CsbgReportService;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;

class CsbgAnnualReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Annual Report';

    protected static ?string $title = 'CSBG Annual Report';

    protected string $view = 'filament.pages.csbg-annual-report';

    protected static string|\UnitEnum|null $navigationGroup = 'CSBG Reports';

    protected static ?int $navigationSort = 1;

    public ?int $fiscalYear = null;

    public ?array $reportData = null;

    public ?string $generatedAt = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function mount(): void
    {
        $settings = CsbgReportSetting::first();
        $this->fiscalYear = $settings?->current_fiscal_year ?? (now()->month >= 10 ? now()->year + 1 : now()->year);

        $this->loadCachedReport();
    }

    public function updatedFiscalYear(): void
    {
        $this->loadCachedReport();
    }

    protected function loadCachedReport(): void
    {
        $cached = Cache::get($this->cacheKey());

        if ($cached) {
            $this->reportData = $cached['data'];
            $this->generatedAt = $cached['generated_at'];
        } else {
            $this->reportData = null;
            $this->generatedAt = null;
        }
    }

    public function generateReport(): void
    {
        [$startDate, $endDate] = $this->getDateRange();

        $service = new CsbgReportService();

        $data = [
            'module4a' => $service->module4SectionA($startDate, $endDate)->toArray(),
            'module4b' => $service->module4SectionB($startDate, $endDate)->toArray(),
            'module4c' => $service->module4SectionC($startDate, $endDate),
            'module2a' => $service->module2SectionA($this->fiscalYear)->toArray(),
            'module2b' => $service->module2SectionB($this->fiscalYear),
            'module2c' => $service->module2SectionC($this->fiscalYear),
            'module3' => CommunityInitiative::where('fiscal_year', $this->fiscalYear)->get()->toArray(),
            'module3b' => $service->module3SectionB($this->fiscalYear)->toArray(),
            'module3c' => $service->module3SectionC($this->fiscalYear)->toArray(),
        ];

        $generatedAt = now()->format('M j, Y g:i A');

        Cache::put($this->cacheKey(), [
            'data' => $data,
            'generated_at' => $generatedAt,
        ], now()->addHours(24));

        $this->reportData = $data;
        $this->generatedAt = $generatedAt;

        Notification::make()
            ->success()
            ->title('Report generated')
            ->body("CSBG Annual Report for FFY {$this->fiscalYear} generated at {$generatedAt}.")
            ->send();
    }

    public function regenerateReport(): void
    {
        Cache::forget($this->cacheKey());
        $this->generateReport();
    }

    protected function cacheKey(): string
    {
        return "csbg-annual-report:{$this->fiscalYear}";
    }

    protected function getDateRange(): array
    {
        $settings = CsbgReportSetting::first();
        $period = $settings?->reporting_period ?? 'oct_sep';

        return match ($period) {
            'oct_sep' => [($this->fiscalYear - 1) . '-10-01', $this->fiscalYear . '-09-30'],
            'jul_jun' => [($this->fiscalYear - 1) . '-07-01', $this->fiscalYear . '-06-30'],
            'jan_dec' => [$this->fiscalYear . '-01-01', $this->fiscalYear . '-12-31'],
            default => [($this->fiscalYear - 1) . '-10-01', $this->fiscalYear . '-09-30'],
        };
    }

    public function getAgencyInfo(): array
    {
        $settings = CsbgReportSetting::first();
        $agency = AgencySetting::current();

        return [
            'entity_name' => $settings?->entity_name ?? $agency?->agency_name ?? '',
            'state' => $settings?->state ?? $agency?->agency_state ?? '',
            'uei' => $settings?->uei ?? '',
            'reporting_period' => match ($settings?->reporting_period ?? 'oct_sep') {
                'oct_sep' => 'October - September',
                'jul_jun' => 'July - June',
                'jan_dec' => 'January - December',
                default => 'October - September',
            },
        ];
    }
}
