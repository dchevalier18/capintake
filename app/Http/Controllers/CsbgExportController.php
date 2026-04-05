<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\CsbgReportSetting;
use App\Services\CsbgReportPdfExporter;
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
            'oct_sep' => [($year - 1) . '-10-01', $year . '-09-30'],
            'jul_jun' => [($year - 1) . '-07-01', $year . '-06-30'],
            'jan_dec' => [$year . '-01-01', $year . '-12-31'],
            default => [($year - 1) . '-10-01', $year . '-09-30'],
        };

        $service = new NpiReportService();
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
}
