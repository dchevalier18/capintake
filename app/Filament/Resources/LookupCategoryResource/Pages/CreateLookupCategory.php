<?php

declare(strict_types=1);

namespace App\Filament\Resources\LookupCategoryResource\Pages;

use App\Filament\Resources\LookupCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLookupCategory extends CreateRecord
{
    protected static string $resource = LookupCategoryResource::class;
}
