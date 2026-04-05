<?php

declare(strict_types=1);

namespace App\Filament\Resources\CommunityInitiativeResource\Pages;

use App\Filament\Resources\CommunityInitiativeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommunityInitiatives extends ListRecords
{
    protected static string $resource = CommunityInitiativeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
