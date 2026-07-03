<?php

declare(strict_types=1);

namespace App\Exports;

use App\Services\NpiReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NpiReportCsvExport
{
    public function __construct(
        protected string $startDate,
        protected string $endDate,
        protected ?int $programId = null,
    ) {}

    public function download(string $filename): StreamedResponse
    {
        $service = new NpiReportService;

        if ($this->programId) {
            $service->forProgram($this->programId);
        }

        $rows = $service->toFlatRows($this->startDate, $this->endDate);

        $titleRows = [
            ['CSBG National Performance Indicators Report'],
            ['Reporting Period: '.$this->startDate.' to '.$this->endDate],
            ['Generated: '.now()->format('m/d/Y h:i A')],
            [],
        ];

        return response()->streamDownload(function () use ($titleRows, $rows): void {
            $handle = fopen('php://output', 'w');

            foreach ($titleRows as $row) {
                fputcsv($handle, $row);
            }

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
