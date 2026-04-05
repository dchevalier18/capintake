<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Services\CsbgReportService;
use Database\Seeders\LookupSeeder;
use Database\Seeders\NpiSeeder;
use Database\Seeders\NpiServiceMappingSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedAll(): void
{
    test()->seed(LookupSeeder::class);
    test()->seed(NpiSeeder::class);
    test()->seed(ProgramSeeder::class);
    test()->seed(NpiServiceMappingSeeder::class);
}

function createServiceForCode(
    string $serviceCode,
    Client $client,
    string $serviceDate,
    float $value = 100.00,
): ServiceRecord {
    $service = Service::where('code', $serviceCode)->firstOrFail();
    $caseworker = User::factory()->caseworker()->create();
    $enrollment = Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $service->program_id,
        'caseworker_id' => $caseworker->id,
    ]);

    return ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'enrollment_id' => $enrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => $serviceDate,
        'value' => $value,
    ]);
}

// =========================================================================
// Unduplicated Counts
// =========================================================================

it('counts unduplicated clients correctly — same client 5 services = 1', function () {
    seedAll();

    $client = Client::factory()->create();
    for ($i = 0; $i < 5; $i++) {
        createServiceForCode('CSBG-VITA', $client, '2025-06-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT));
    }

    $report = (new CsbgReportService())->module4SectionA('2025-01-01', '2025-12-31');
    $goal3 = $report->firstWhere('goal_number', 3);
    $indicator = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    expect($indicator['unduplicated_clients'])->toBe(1)
        ->and($indicator['total_services'])->toBe(5);
});

it('counts multiple clients correctly', function () {
    seedAll();

    $clientA = Client::factory()->create();
    $clientB = Client::factory()->create();
    $clientC = Client::factory()->create();

    createServiceForCode('CSBG-VITA', $clientA, '2025-06-01');
    createServiceForCode('CSBG-VITA', $clientB, '2025-06-01');
    createServiceForCode('CSBG-VITA', $clientC, '2025-06-01');

    $report = (new CsbgReportService())->module4SectionA('2025-01-01', '2025-12-31');
    $goal3 = $report->firstWhere('goal_number', 3);
    $indicator = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    expect($indicator['unduplicated_clients'])->toBe(3);
});

// =========================================================================
// Date Filtering
// =========================================================================

it('excludes services outside the reporting period', function () {
    seedAll();

    $client = Client::factory()->create();
    createServiceForCode('CSBG-VITA', $client, '2025-06-01');
    createServiceForCode('CSBG-VITA', $client, '2024-01-01'); // outside period

    $report = (new CsbgReportService())->module4SectionA('2025-01-01', '2025-12-31');
    $goal3 = $report->firstWhere('goal_number', 3);
    $indicator = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    expect($indicator['unduplicated_clients'])->toBe(1)
        ->and($indicator['total_services'])->toBe(1);
});

// =========================================================================
// Demographic Breakdowns
// =========================================================================

it('demographic breakdowns sum to the total', function () {
    seedAll();

    $clientA = Client::factory()->create(['gender' => 'male', 'race' => 'white']);
    $clientB = Client::factory()->create(['gender' => 'female', 'race' => 'black_african_american']);

    createServiceForCode('CSBG-VITA', $clientA, '2025-06-01');
    createServiceForCode('CSBG-VITA', $clientB, '2025-06-01');

    $report = (new CsbgReportService())->module4SectionA('2025-01-01', '2025-12-31');
    $goal3 = $report->firstWhere('goal_number', 3);
    $indicator = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    $genderSum = array_sum($indicator['by_gender']);
    $raceSum = array_sum($indicator['by_race']);

    expect($genderSum)->toBe($indicator['unduplicated_clients'])
        ->and($raceSum)->toBe($indicator['unduplicated_clients']);
});

// =========================================================================
// Program Filtering
// =========================================================================

it('filters by program when specified', function () {
    seedAll();

    $client = Client::factory()->create();
    createServiceForCode('CSBG-VITA', $client, '2025-06-01');
    createServiceForCode('EMRG-FOOD', $client, '2025-06-01');

    $csbgProgram = \App\Models\Program::where('code', 'CSBG')->first();

    $report = (new CsbgReportService())
        ->forProgram($csbgProgram->id)
        ->module4SectionA('2025-01-01', '2025-12-31');

    // CSBG-VITA maps to FNPI-3a (Goal 3), EMRG-FOOD maps to FNPI-7a (Goal 7)
    $goal7 = $report->firstWhere('goal_number', 7);

    // When filtered to CSBG, Goal 7 should have 0 clients (EMRG is filtered out)
    if ($goal7) {
        $indicator7a = collect($goal7['indicators'])->firstWhere('indicator_code', 'FNPI-7a');
        expect($indicator7a['unduplicated_clients'])->toBe(0);
    }
});
