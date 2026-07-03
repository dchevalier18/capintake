<?php

declare(strict_types=1);

namespace App\Filament\Resources\CnpiResultResource\Pages;

use App\Filament\Resources\CnpiResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCnpiResults extends ListRecords
{
    protected static string $resource = CnpiResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
