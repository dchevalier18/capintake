<?php

declare(strict_types=1);

namespace App\Filament\Resources\FederalPovertyLevelResource\Pages;

use App\Filament\Resources\FederalPovertyLevelResource;
use App\Models\FederalPovertyLevel;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListFederalPovertyLevels extends ListRecords
{
    protected static string $resource = FederalPovertyLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Year'),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        if (! FederalPovertyLevel::isCurrentYearSeeded()) {
            $latest = FederalPovertyLevel::latestYearAvailable();

            Notification::make()
                ->warning()
                ->title('Poverty guidelines out of date')
                ->body(
                    'No HHS poverty guidelines are loaded for '.now()->year.'. '
                    .($latest
                        ? "Eligibility calculations are falling back to the {$latest} guidelines. "
                        : 'Eligibility calculations will show N/A. ')
                    .'HHS publishes each year\'s guidelines in mid-January — add them here when released.'
                )
                ->persistent()
                ->send();
        }
    }
}
