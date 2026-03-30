<?php

declare(strict_types=1);

namespace App\Exports;

use App\Services\NpiReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NpiReportCsvExport implements FromArray, WithTitle, WithStyles
{
    public function __construct(
        protected string $startDate,
        protected string $endDate,
        protected ?int $programId = null,
    ) {}

    public function array(): array
    {
        $service = new NpiReportService();

        if ($this->programId) {
            $service->forProgram($this->programId);
        }

        $rows = [];

        $rows[] = ['CSBG National Performance Indicators Report'];
        $rows[] = ['Reporting Period: ' . $this->startDate . ' to ' . $this->endDate];
        $rows[] = ['Generated: ' . now()->format('m/d/Y h:i A')];
        $rows[] = [];

        $dataRows = $service->toFlatRows($this->startDate, $this->endDate);
        foreach ($dataRows as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function title(): string
    {
        return 'NPI Report';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            5 => ['font' => ['bold' => true]],
        ];
    }
}
