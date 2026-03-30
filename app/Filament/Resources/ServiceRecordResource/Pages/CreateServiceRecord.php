<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceRecordResource\Pages;

use App\Filament\Resources\ServiceRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRecord extends CreateRecord
{
    protected static string $resource = ServiceRecordResource::class;
}
