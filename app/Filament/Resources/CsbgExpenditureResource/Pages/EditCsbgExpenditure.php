<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsbgExpenditureResource\Pages;

use App\Filament\Resources\CsbgExpenditureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCsbgExpenditure extends EditRecord
{
    protected static string $resource = CsbgExpenditureResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
