<?php

declare(strict_types=1);

namespace App\Filament\Resources\LookupCategoryResource\Pages;

use App\Filament\Resources\LookupCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLookupCategory extends EditRecord
{
    protected static string $resource = LookupCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! $this->record->is_system),
        ];
    }
}
