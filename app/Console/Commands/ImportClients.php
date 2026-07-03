<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ClientImportService;
use Illuminate\Console\Command;

class ImportClients extends Command
{
    protected $signature = 'capintake:import-clients
                            {file : Path to the CSV file}
                            {--dry-run : Report what would be imported without writing}';

    protected $description = 'Import clients from a CSV export (CAP60, empowOR, spreadsheets). Columns are auto-matched from headers.';

    public function handle(ClientImportService $importer): int
    {
        $file = $this->argument('file');

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        [$headers, $rows] = $importer->parse($file);
        $mapping = $importer->guessMapping($headers);

        $this->info('Column mapping (from CSV headers):');
        foreach ($mapping as $index => $field) {
            $this->line(sprintf(
                '  %-30s -> %s',
                $headers[$index] ?? "column {$index}",
                $field ? (ClientImportService::TARGET_FIELDS[$field] ?? $field) : '(ignored)',
            ));
        }

        $dryRun = (bool) $this->option('dry-run');
        $result = $importer->import($rows, $mapping, $dryRun);

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '').sprintf(
            '%d rows: %d %s, %d duplicates skipped, %d errors.',
            $result['total'],
            $result['created'],
            $dryRun ? 'would be created' : 'created',
            $result['skipped_duplicates'],
            count($result['errors']),
        ));

        foreach ($result['errors'] as $rowNumber => $message) {
            $this->warn("  Row {$rowNumber}: {$message}");
        }

        return self::SUCCESS;
    }
}
