<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Exports\CsbgModule4XlsxExport;
use App\Exports\Module4RowBuilder;
use App\Models\CsbgReportSetting;
use App\Services\CsbgReportPdfExporter;
use App\Services\CsbgReportService;
use App\Services\NpiReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsbgExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, [UserRole::Admin, UserRole::Supervisor])) {
            abort(403);
        }

        $year = (int) $request->query('year', (string) (now()->month >= 10 ? now()->year + 1 : now()->year));

        $settings = CsbgReportSetting::first();
        $period = $settings?->reporting_period ?? 'oct_sep';

        [$startDate, $endDate] = match ($period) {
            'oct_sep' => [($year - 1).'-10-01', $year.'-09-30'],
            'jul_jun' => [($year - 1).'-07-01', $year.'-06-30'],
            'jan_dec' => [$year.'-01-01', $year.'-12-31'],
            default => [($year - 1).'-10-01', $year.'-09-30'],
        };

        $service = new NpiReportService;
        $rows = $service->toFlatRows($startDate, $endDate);

        $filename = "csbg-fnpi-report-ffy{$year}.csv";

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function pdf(Request $request)
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, [UserRole::Admin, UserRole::Supervisor])) {
            abort(403);
        }

        $year = (int) $request->query('year', (string) (now()->month >= 10 ? now()->year + 1 : now()->year));

        $pdf = (new CsbgReportPdfExporter($year))->generate();

        return $pdf->download("csbg-annual-report-ffy{$year}.pdf");
    }

    /**
     * Module 4 workbook (FNPI / Services / All Characteristics) in the
     * NASCSP SmartForm-style layout used for agency-to-state submission.
     */
    public function xlsx(Request $request)
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, [UserRole::Admin, UserRole::Supervisor])) {
            abort(403);
        }

        $year = (int) $request->query('year', (string) (now()->month >= 10 ? now()->year + 1 : now()->year));
        [$startDate, $endDate] = $this->fiscalPeriod($year);

        $builder = new Module4RowBuilder(new CsbgReportService, $startDate, $endDate);
        $path = (new CsbgModule4XlsxExport($builder))->write();

        return response()->download($path, "csbg-module4-ffy{$year}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Module 4 as a single CSV (section header rows between sections) for
     * systems that can't take the workbook.
     */
    public function module4Csv(Request $request): StreamedResponse
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, [UserRole::Admin, UserRole::Supervisor])) {
            abort(403);
        }

        $year = (int) $request->query('year', (string) (now()->month >= 10 ? now()->year + 1 : now()->year));
        [$startDate, $endDate] = $this->fiscalPeriod($year);

        $builder = new Module4RowBuilder(new CsbgReportService, $startDate, $endDate);

        $sections = [
            'MODULE 4 SECTION A — FNPIs' => $builder->sectionARows(),
            'MODULE 4 SECTION B — SERVICES' => $builder->sectionBRows(),
            'MODULE 4 SECTION C — ALL CHARACTERISTICS' => $builder->sectionCRows(),
        ];

        return response()->streamDownload(function () use ($sections): void {
            $handle = fopen('php://output', 'w');
            foreach ($sections as $title => $rows) {
                fputcsv($handle, [$title]);
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
                fputcsv($handle, []);
            }
            fclose($handle);
        }, "csbg-module4-ffy{$year}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Resolve the fiscal period dates for a report year from settings.
     *
     * @return array{string, string}
     */
    protected function fiscalPeriod(int $year): array
    {
        $period = CsbgReportSetting::first()?->reporting_period ?? 'oct_sep';

        return match ($period) {
            'jul_jun' => [($year - 1).'-07-01', $year.'-06-30'],
            'jan_dec' => [$year.'-01-01', $year.'-12-31'],
            default => [($year - 1).'-10-01', $year.'-09-30'],
        };
    }
}
