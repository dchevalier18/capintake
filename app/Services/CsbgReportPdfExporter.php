<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgencySetting;
use App\Models\CommunityInitiative;
use App\Models\CsbgReportSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class CsbgReportPdfExporter
{
    protected int $fiscalYear;

    protected string $startDate;

    protected string $endDate;

    public function __construct(int $fiscalYear)
    {
        $this->fiscalYear = $fiscalYear;

        $settings = CsbgReportSetting::first();
        $period = $settings?->reporting_period ?? 'oct_sep';

        [$this->startDate, $this->endDate] = match ($period) {
            'oct_sep' => [($fiscalYear - 1) . '-10-01', $fiscalYear . '-09-30'],
            'jul_jun' => [($fiscalYear - 1) . '-07-01', $fiscalYear . '-06-30'],
            'jan_dec' => [$fiscalYear . '-01-01', $fiscalYear . '-12-31'],
            default => [($fiscalYear - 1) . '-10-01', $fiscalYear . '-09-30'],
        };
    }

    public function generate(): \Barryvdh\DomPDF\PDF
    {
        $service = new CsbgReportService();
        $agency = $this->getAgencyInfo();

        $data = [
            'agency' => $agency,
            'fiscalYear' => $this->fiscalYear,
            'module4a' => $service->module4SectionA($this->startDate, $this->endDate)->toArray(),
            'module4b' => $service->module4SectionB($this->startDate, $this->endDate)->toArray(),
            'module4c' => $service->module4SectionC($this->startDate, $this->endDate),
            'module2a' => $service->module2SectionA($this->fiscalYear)->toArray(),
            'module3' => CommunityInitiative::where('fiscal_year', $this->fiscalYear)->get()->toArray(),
        ];

        // Render all sections
        $html = '';
        $sections = ['module4a-fnpi', 'module4b-services', 'module4c-characteristics', 'module2a-expenditures', 'module3a-initiatives'];

        foreach ($sections as $i => $section) {
            $html .= view("reports.csbg.{$section}", $data)->render();
            if ($i < count($sections) - 1) {
                $html .= '<div class="page-break"></div>';
            }
        }

        return Pdf::loadHTML($html)
            ->setPaper('letter', 'portrait');
    }

    protected function getAgencyInfo(): array
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
