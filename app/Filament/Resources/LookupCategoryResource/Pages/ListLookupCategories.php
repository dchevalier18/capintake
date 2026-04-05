<?php

declare(strict_types=1);

namespace App\Filament\Resources\LookupCategoryResource\Pages;

use App\Filament\Resources\LookupCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLookupCategories extends ListRecords
{
    protected static string $resource = LookupCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
