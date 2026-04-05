<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\CsbgReportSetting;
use App\Services\TrendAnalysisService;
use Filament\Widgets\Widget;

class TargetsVsActuals extends Widget
{
    protected string $view = 'filament.widgets.targets-vs-actuals';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public ?array $indicators = null;

    public int $fiscalYear;

    public function mount(): void
    {
        $this->fiscalYear = CsbgReportSetting::current()->current_fiscal_year;
        $this->loadData();
    }

    public function loadData(): void
    {
        $service = new TrendAnalysisService();
        $results = $service->targetsVsActuals($this->fiscalYear);

        // Only show indicators that have a target set
        $this->indicators = $results
            ->filter(fn ($row) => $row['target'] > 0)
            ->take(10)
            ->values()
            ->toArray();
    }
}
