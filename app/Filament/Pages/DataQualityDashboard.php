<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Services\DataQualityService;
use Filament\Pages\Page;

class DataQualityDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Data Quality';

    protected static ?string $title = 'Data Quality Dashboard';

    protected string $view = 'filament.pages.data-quality-dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 10;

    public ?array $overview = null;

    public ?array $leastComplete = null;

    public ?array $duplicates = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $service = new DataQualityService();

        $this->overview = $service->agencyCompletenessOverview();
        $this->leastComplete = $service->leastCompleteClients(15)->map(fn ($item) => [
            'id' => $item['client']->id,
            'name' => $item['client']->fullName(),
            'score' => $item['score'],
            'missing' => $item['missing'],
        ])->toArray();
        $this->duplicates = $service->duplicateDetection()->toArray();
    }
}
