<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Services\ClientImportService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Admin-only CSV client import: upload a CSV export from another system
 * (CAP60, empowOR, spreadsheets), review/adjust the auto-guessed column
 * mapping, preview the dry-run result, then import.
 */
class ClientImport extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Client Import';

    protected static ?string $title = 'Import Clients from CSV';

    protected string $view = 'filament.pages.client-import';

    protected static string|\UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?int $navigationSort = 25;

    public ?TemporaryUploadedFile $csvFile = null;

    /** @var list<string> */
    public array $headers = [];

    /** @var list<list<string|null>> */
    public array $rows = [];

    /** @var array<int, string|null> */
    public array $mapping = [];

    public ?array $preview = null;

    public ?array $result = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->role === UserRole::Admin;
    }

    public function analyze(): void
    {
        $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $importer = app(ClientImportService::class);

        try {
            [$this->headers, $this->rows] = $importer->parse($this->csvFile->getRealPath());
        } catch (\RuntimeException $e) {
            Notification::make()->danger()->title('Could not read CSV')->body($e->getMessage())->send();

            return;
        }

        $this->mapping = $importer->guessMapping($this->headers);
        $this->preview = null;
        $this->result = null;
    }

    public function dryRun(): void
    {
        $importer = app(ClientImportService::class);

        try {
            $this->preview = $importer->import($this->rows, $this->normalizedMapping(), dryRun: true);
        } catch (\InvalidArgumentException $e) {
            Notification::make()->danger()->title('Mapping incomplete')->body($e->getMessage())->send();
        }
    }

    public function import(): void
    {
        $importer = app(ClientImportService::class);

        try {
            $this->result = $importer->import($this->rows, $this->normalizedMapping());
        } catch (\InvalidArgumentException $e) {
            Notification::make()->danger()->title('Mapping incomplete')->body($e->getMessage())->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Import complete')
            ->body(sprintf(
                '%d clients created, %d duplicates skipped, %d rows with issues.',
                $this->result['created'],
                $this->result['skipped_duplicates'],
                count($this->result['errors']),
            ))
            ->send();
    }

    public function getTargetFieldsProperty(): array
    {
        return ClientImportService::TARGET_FIELDS;
    }

    /**
     * Livewire selects post empty strings; the importer expects null.
     *
     * @return array<int, string|null>
     */
    protected function normalizedMapping(): array
    {
        return array_map(fn ($field) => $field === '' ? null : $field, $this->mapping);
    }
}
