<?php

declare(strict_types=1);

namespace App\Filament\Resources\CommunityInitiativeResource\Pages;

use App\Filament\Resources\CommunityInitiativeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityInitiative extends CreateRecord
{
    protected static string $resource = CommunityInitiativeResource::class;
}
