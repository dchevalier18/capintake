<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutcomeResource\Pages;

use App\Filament\Resources\OutcomeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOutcome extends CreateRecord
{
    protected static string $resource = OutcomeResource::class;
}
