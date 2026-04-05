<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsbgExpenditureResource\Pages;

use App\Filament\Resources\CsbgExpenditureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCsbgExpenditures extends ListRecords
{
    protected static string $resource = CsbgExpenditureResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
