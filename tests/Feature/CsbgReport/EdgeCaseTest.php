<?php

declare(strict_types=1);

use App\Enums\IntakeStatus;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Services\CsbgReportService;
use App\Services\NpiReportService;
use Database\Seeders\CsbgSrvCategorySeeder;
use Database\Seeders\LookupSeeder;
use Database\Seeders\NpiSeeder;
use Database\Seeders\NpiServiceMappingSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->seed(NpiSeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(NpiServiceMappingSeeder::class);
});

function makeServiceRecord(string $serviceCode, Client $client, string $date, float $value = 100.00): ServiceRecord
{
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
        'service_date' => $date,
        'value' => $value,
    ]);
}

// =========================================================================
// Cross-category counting
// =========================================================================

it('client with services in multiple NPI categories counts once per category but once total', function () {
    $client = Client::factory()->create();

    // CSBG-VITA maps to FNPI-3a (Goal 3)
    // EMRG-FOOD maps to FNPI-7a (Goal 7)
    makeServiceRecord('CSBG-VITA', $client, '2025-06-01');
    makeServiceRecord('EMRG-FOOD', $client, '2025-06-01');

    $npiService = new NpiReportService();
    $report = $npiService->generate('2025-01-01', '2025-12-31');

    // Client should appear once in Goal 3
    $goal3 = $report->firstWhere('goal_number', 3);
    $fnpi3a = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');
    expect($fnpi3a['unduplicated_clients'])->toBe(1);

    // Client should appear once in Goal 7
    $goal7 = $report->firstWhere('goal_number', 7);
    $fnpi7a = collect($goal7['indicators'])->firstWhere('indicator_code', 'FNPI-7a');
    expect($fnpi7a['unduplicated_clients'])->toBe(1);

    // Grand total should be 1 (same client across all categories)
    $grandTotal = $npiService->grandTotalUnduplicatedClients('2025-01-01', '2025-12-31');
    expect($grandTotal)->toBe(1);
});

// =========================================================================
// Reporting period boundary — federal fiscal year Oct 1 - Sep 30
// =========================================================================

it('service on Sep 30 is included in FFY, Oct 1 of next year is excluded', function () {
    $client = Client::factory()->create();

    // FFY 2025 = Oct 1, 2024 through Sep 30, 2025
    makeServiceRecord('CSBG-VITA', $client, '2025-09-30'); // included
    makeServiceRecord('CSBG-VITA', $client, '2025-10-01'); // excluded (next FFY)

    $npiService = new NpiReportService();
    $report = $npiService->generate('2024-10-01', '2025-09-30');

    $goal3 = $report->firstWhere('goal_number', 3);
    $fnpi3a = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    expect($fnpi3a['unduplicated_clients'])->toBe(1)
        ->and($fnpi3a['total_services'])->toBe(1);
});

it('service on Oct 1 start of FFY is included', function () {
    $client = Client::factory()->create();

    makeServiceRecord('CSBG-VITA', $client, '2024-10-01'); // first day of FFY 2025

    $npiService = new NpiReportService();
    $report = $npiService->generate('2024-10-01', '2025-09-30');

    $goal3 = $report->firstWhere('goal_number', 3);
    $fnpi3a = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    expect($fnpi3a['unduplicated_clients'])->toBe(1);
});

// =========================================================================
// Empty database produces valid report
// =========================================================================

it('empty database produces a valid report with all zeros — no errors', function () {
    $npiService = new NpiReportService();
    $report = $npiService->generate('2025-01-01', '2025-12-31');

    // Should have all 7 goals
    expect($report)->toHaveCount(7);

    // All indicators should have 0 clients
    foreach ($report as $goal) {
        expect($goal['goal_total_clients'])->toBe(0);
        foreach ($goal['indicators'] as $indicator) {
            expect($indicator['unduplicated_clients'])->toBe(0);
        }
    }

    // Grand total should be 0
    $grandTotal = $npiService->grandTotalUnduplicatedClients('2025-01-01', '2025-12-31');
    expect($grandTotal)->toBe(0);

    // Flat rows should still work
    $rows = $npiService->toFlatRows('2025-01-01', '2025-12-31');
    expect($rows)->not->toBeEmpty();
});

it('Module 4 Section C with empty database produces valid structure', function () {
    $csbgService = new CsbgReportService();
    $report = $csbgService->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['total_unduplicated_individuals'])->toBe(0)
        ->and($report['by_gender'])->toBeArray()
        ->and($report['by_race'])->toBeArray()
        ->and($report['by_fpl_bracket'])->toBeArray();
});

// =========================================================================
// FPL edge cases
// =========================================================================

it('household with no income records shows as unknown in FPL brackets', function () {
    $client = Client::factory()->create(['intake_status' => IntakeStatus::Complete]);

    $service = Service::where('code', 'CSBG-CM')->first();
    $caseworker = User::factory()->caseworker()->create();
    $enrollment = Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $service->program_id,
        'caseworker_id' => $caseworker->id,
        'fpl_percent_at_enrollment' => null, // no FPL data
    ]);

    ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'enrollment_id' => $enrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => '2025-06-01',
    ]);

    $report = (new CsbgReportService())->fplBracketBreakdown('2025-01-01', '2025-12-31');

    expect($report['unknown'])->toBe(1);
});

it('client exactly at 100% FPL falls in 76-100% bracket', function () {
    $client = Client::factory()->create(['intake_status' => IntakeStatus::Complete]);

    $service = Service::where('code', 'CSBG-CM')->first();
    $caseworker = User::factory()->caseworker()->create();
    $enrollment = Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $service->program_id,
        'caseworker_id' => $caseworker->id,
        'fpl_percent_at_enrollment' => 100,
    ]);

    ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'enrollment_id' => $enrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => '2025-06-01',
    ]);

    $report = (new CsbgReportService())->fplBracketBreakdown('2025-01-01', '2025-12-31');

    expect($report['76-100%'])->toBe(1);
});

// =========================================================================
// Module 4 Section B edge cases
// =========================================================================

it('SRV report works with no SRV category data seeded', function () {
    // No CsbgSrvCategorySeeder run
    $report = (new CsbgReportService())->module4SectionB('2025-01-01', '2025-12-31');

    // Should return empty collection (no categories to report on)
    expect($report)->toBeEmpty();
});

it('SRV report with categories seeded but no services shows all zeros', function () {
    $this->seed(CsbgSrvCategorySeeder::class);

    $report = (new CsbgReportService())->module4SectionB('2025-01-01', '2025-12-31');

    expect($report)->not->toBeEmpty();

    foreach ($report as $domain) {
        expect($domain['domain_total'])->toBe(0);
    }
});
