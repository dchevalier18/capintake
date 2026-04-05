<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NpiIndicator;
use App\Models\Service;
use Illuminate\Database\Seeder;

class NpiServiceMappingSeeder extends Seeder
{
    /**
     * Map service codes to FNPI indicator codes.
     * A single service can map to multiple indicators.
     */
    public function run(): void
    {
        $mappings = [
            // CSBG services
            'CSBG-ERT' => ['FNPI-1a', 'FNPI-1b'],
            'CSBG-FLW' => ['FNPI-2h', 'FNPI-3d'],
            'CSBG-VITA' => ['FNPI-3a'],
            'CSBG-IR' => ['FNPI-3c'],
            'CSBG-CM' => ['FNPI-1c', 'FNPI-3c', 'FNPI-4c'],

            // Emergency services
            'EMRG-RENT' => ['FNPI-4b', 'FNPI-4e', 'FNPI-7a'],
            'EMRG-FOOD' => ['FNPI-7a'],
            'EMRG-UTIL' => ['FNPI-7a'],
            'EMRG-RX' => ['FNPI-5b', 'FNPI-7a'],
            'EMRG-CLO' => ['FNPI-7a'],

            // Weatherization services
            'WAP-AUDIT' => ['FNPI-4h'],
            'WAP-INS' => ['FNPI-4h'],
            'WAP-FURN' => ['FNPI-4h'],
            'WAP-SEAL' => ['FNPI-4h'],
            'WAP-WIN' => ['FNPI-4h'],
        ];

        foreach ($mappings as $serviceCode => $indicatorCodes) {
            $service = Service::where('code', $serviceCode)->first();
            if (! $service) {
                continue;
            }

            $indicatorIds = NpiIndicator::whereIn('indicator_code', $indicatorCodes)
                ->pluck('id')
                ->toArray();

            $service->npiIndicators()->syncWithoutDetaching($indicatorIds);
        }
    }
}
