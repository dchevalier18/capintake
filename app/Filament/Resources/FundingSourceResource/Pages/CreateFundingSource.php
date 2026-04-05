<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundingSourceResource\Pages;

use App\Filament\Resources\FundingSourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFundingSource extends CreateRecord
{
    protected static string $resource = FundingSourceResource::class;
}
