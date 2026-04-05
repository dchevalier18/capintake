<?php

declare(strict_types=1);

namespace App\Filament\Resources\FederalPovertyLevelResource\Pages;

use App\Filament\Resources\FederalPovertyLevelResource;
use App\Models\FederalPovertyLevel;
use Filament\Actions;
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
}
