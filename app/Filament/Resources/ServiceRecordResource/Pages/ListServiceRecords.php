<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceRecordResource\Pages;

use App\Filament\Resources\ServiceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceRecords extends ListRecords
{
    protected static string $resource = ServiceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
