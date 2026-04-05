<?php

declare(strict_types=1);

use App\Filament\Pages\NpiReport;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Services\NpiReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Helper: seed NPI goals, programs, services, and their mappings.
 */
function seedNpiData(): void
{
    test()->seed(\Database\Seeders\LookupSeeder::class);
    test()->seed(\Database\Seeders\NpiSeeder::class);
    test()->seed(\Database\Seeders\ProgramSeeder::class);
    test()->seed(\Database\Seeders\NpiServiceMappingSeeder::class);
}

/**
 * Helper: create a service record for a seeded service code.
 */
function createServiceRecordForCode(
    string $serviceCode,
    ?Client $client = null,
    ?string $serviceDate = null,
    float $value = 100.00,
): ServiceRecord {
    $service = Service::where('code', $serviceCode)->firstOrFail();
    $program = $service->program;
    $caseworker = User::factory()->caseworker()->create();
    $client ??= Client::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $program->id,
        'caseworker_id' => $caseworker->id,
    ]);

    return ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'enrollment_id' => $enrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => $serviceDate ?? now()->format('Y-m-d'),
        'value' => $value,
    ]);
}

// =============================================================================
// NpiReportService — Core Counts
// =============================================================================

it('generate returns data for all 7 goals', function () {
    seedNpiData();

    $service = new NpiReportService();
    $report = $service->generate('2025-01-01', '2026-12-31');

    expect($report)->toHaveCount(7);
    expect($report->pluck('goal_number')->toArray())->toBe([1, 2, 3, 4, 5, 6, 7]);
});

it('unduplicated count is correct — same client with 2 service records counts as 1', function () {
    seedNpiData();

    $client = Client::factory()->create();
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-01', 50.00);
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-15', 75.00);

    $service = new NpiReportService();
    $report = $service->generate('2025-01-01', '2025-12-31');

    $goal3 = $report->firstWhere('goal_number', 3);
    $indicator31 = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    expect($indicator31['unduplicated_clients'])->toBe(1);
    expect($indicator31['total_services'])->toBe(2);
});

it('service records outside date range are excluded', function () {
    seedNpiData();

    $client = Client::factory()->create();
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-01', 100.00);
    createServiceRecordForCode('CSBG-VITA', $client, '2024-01-01', 200.00);

    $service = new NpiReportService();
    $report = $service->generate('2025-01-01', '2025-12-31');

    $goal3 = $report->firstWhere('goal_number', 3);
    $indicator31 = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    expect($indicator31['unduplicated_clients'])->toBe(1);
    expect($indicator31['total_services'])->toBe(1);
    expect($indicator31['total_value'])->toBe(100.00);
});

it('goal-level unduplicated count works — client under 2 indicators in same goal counts once', function () {
    seedNpiData();

    $client = Client::factory()->create();
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-01');
    createServiceRecordForCode('CSBG-IR', $client, '2025-06-15');

    $service = new NpiReportService();
    $report = $service->generate('2025-01-01', '2025-12-31');

    $goal3 = $report->firstWhere('goal_number', 3);
    expect($goal3['goal_total_clients'])->toBe(1);
});

it('grandTotalUnduplicatedClients returns correct count', function () {
    seedNpiData();

    $clientA = Client::factory()->create();
    $clientB = Client::factory()->create();

    createServiceRecordForCode('CSBG-VITA', $clientA, '2025-06-01');
    createServiceRecordForCode('EMRG-FOOD', $clientA, '2025-06-15');
    createServiceRecordForCode('EMRG-RENT', $clientB, '2025-07-01');

    $service = new NpiReportService();
    expect($service->grandTotalUnduplicatedClients('2025-01-01', '2025-12-31'))->toBe(2);
});

// =============================================================================
// NpiReportService — Program Filter
// =============================================================================

it('program filter limits results to a single program', function () {
    seedNpiData();

    $client = Client::factory()->create();
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-01');
    createServiceRecordForCode('EMRG-FOOD', $client, '2025-06-01');

    $csbgProgram = Program::where('code', 'CSBG')->first();
    $emrgProgram = Program::where('code', 'EMRG')->first();

    // CSBG-only: client should appear under Goal 3 (CSBG-VITA → 3.1)
    $service = (new NpiReportService())->forProgram($csbgProgram->id);
    $report = $service->generate('2025-01-01', '2025-12-31');

    $goal3 = $report->firstWhere('goal_number', 3);
    expect($goal3)->not->toBeNull();
    $indicator31 = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');
    expect($indicator31)->not->toBeNull();
    expect($indicator31['unduplicated_clients'])->toBe(1);

    // CSBG-only: grand total should be 1 (the CSBG client)
    expect($service->grandTotalUnduplicatedClients('2025-01-01', '2025-12-31'))->toBe(1);

    // EMRG-only: client should appear under Goal 7 (EMRG-FOOD → 7.1, 7.2)
    $service2 = (new NpiReportService())->forProgram($emrgProgram->id);
    $report2 = $service2->generate('2025-01-01', '2025-12-31');

    $goal3b = $report2->firstWhere('goal_number', 3);
    expect($goal3b)->not->toBeNull();
    $indicator31b = collect($goal3b['indicators'])->firstWhere('indicator_code', 'FNPI-3a');
    expect($indicator31b)->not->toBeNull();
    expect($indicator31b['unduplicated_clients'])->toBe(0);

    expect($service2->grandTotalUnduplicatedClients('2025-01-01', '2025-12-31'))->toBe(1);
});

it('program filter applies to grand total', function () {
    seedNpiData();

    $client = Client::factory()->create();
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-01');
    createServiceRecordForCode('EMRG-FOOD', $client, '2025-06-01');

    $csbgProgram = Program::where('code', 'CSBG')->first();

    // Unfiltered: 1 client
    $all = new NpiReportService();
    expect($all->grandTotalUnduplicatedClients('2025-01-01', '2025-12-31'))->toBe(1);

    // CSBG only: still 1 (same client)
    $filtered = (new NpiReportService())->forProgram($csbgProgram->id);
    expect($filtered->grandTotalUnduplicatedClients('2025-01-01', '2025-12-31'))->toBe(1);
});

// =============================================================================
// NpiReportService — Demographics
// =============================================================================

it('generate includes demographic breakdowns per indicator', function () {
    seedNpiData();

    $client = Client::factory()->create([
        'race' => 'black_african_american',
        'gender' => 'female',
        'date_of_birth' => now()->subYears(35),
    ]);
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-01');

    $service = new NpiReportService();
    $report = $service->generate('2025-01-01', '2025-12-31');

    $goal3 = $report->firstWhere('goal_number', 3);
    $indicator31 = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    expect($indicator31['by_race'])->toHaveKey('black_african_american');
    expect($indicator31['by_race']['black_african_american'])->toBe(1);

    expect($indicator31['by_gender'])->toHaveKey('female');
    expect($indicator31['by_gender']['female'])->toBe(1);

    expect($indicator31['by_age'])->toHaveKey('25-44');
    expect($indicator31['by_age']['25-44'])->toBe(1);
});

it('demographic breakdowns are unduplicated per indicator', function () {
    seedNpiData();

    $client = Client::factory()->create(['race' => 'white', 'gender' => 'male']);
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-01');
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-15');

    $service = new NpiReportService();
    $report = $service->generate('2025-01-01', '2025-12-31');

    $goal3 = $report->firstWhere('goal_number', 3);
    $indicator31 = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    // Same client twice should count as 1 in demographics
    expect($indicator31['by_race']['white'])->toBe(1);
    expect($indicator31['by_gender']['male'])->toBe(1);
});

it('multiple clients show correct demographic counts', function () {
    seedNpiData();

    $clientA = Client::factory()->create(['race' => 'white', 'gender' => 'male', 'date_of_birth' => now()->subYears(30)]);
    $clientB = Client::factory()->create(['race' => 'black_african_american', 'gender' => 'female', 'date_of_birth' => now()->subYears(50)]);
    $clientC = Client::factory()->create(['race' => 'white', 'gender' => 'female', 'date_of_birth' => now()->subYears(70)]);

    createServiceRecordForCode('CSBG-VITA', $clientA, '2025-06-01');
    createServiceRecordForCode('CSBG-VITA', $clientB, '2025-06-01');
    createServiceRecordForCode('CSBG-VITA', $clientC, '2025-06-01');

    $service = new NpiReportService();
    $report = $service->generate('2025-01-01', '2025-12-31');

    $goal3 = $report->firstWhere('goal_number', 3);
    $indicator31 = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    expect($indicator31['unduplicated_clients'])->toBe(3);
    expect($indicator31['by_race']['white'])->toBe(2);
    expect($indicator31['by_race']['black_african_american'])->toBe(1);
    expect($indicator31['by_gender']['male'])->toBe(1);
    expect($indicator31['by_gender']['female'])->toBe(2);
    expect($indicator31['by_age']['25-44'])->toBe(1);
    expect($indicator31['by_age']['45-54'])->toBe(1);
    expect($indicator31['by_age']['65-74'])->toBe(1);
});

// =============================================================================
// NpiReportService — CSV Flat Rows
// =============================================================================

it('toFlatRows includes demographic columns in header', function () {
    seedNpiData();

    $service = new NpiReportService();
    $rows = $service->toFlatRows('2025-01-01', '2025-12-31');

    $header = $rows[0];
    expect($header)->toContain('NPI Code');
    expect($header)->toContain('Individuals Served');
    expect($header)->toContain('Target');
    expect($header)->toContain('Actual Results');
    expect($header)->toContain('Race: White');
    expect($header)->toContain('Race: Black or African American');
    expect($header)->toContain('Gender: Male');
    expect($header)->toContain('Gender: Female');
    expect($header)->toContain('Age: 25-44');
    expect($header)->toContain('Age: 75+');
});

it('toFlatRows has correct row count', function () {
    seedNpiData();

    $service = new NpiReportService();
    $rows = $service->toFlatRows('2025-01-01', '2025-12-31');

    // 1 header + 7 goal rows + 60 indicator rows + 1 grand total = 69
    expect($rows)->toHaveCount(69);
});

// =============================================================================
// NPI Report Page
// =============================================================================

it('NPI report page renders for authenticated admin', function () {
    seedNpiData();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(NpiReport::class)
        ->assertSuccessful();
});

it('generateReport sets reportData and grandTotal', function () {
    seedNpiData();

    $client = Client::factory()->create();
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-01');

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(NpiReport::class)
        ->set('startDate', '2025-01-01')
        ->set('endDate', '2025-12-31')
        ->call('generateReport')
        ->assertSet('grandTotal', 1)
        ->assertNotSet('reportData', null);
});

it('generateReport respects program filter', function () {
    seedNpiData();

    $client = Client::factory()->create();
    createServiceRecordForCode('CSBG-VITA', $client, '2025-06-01');
    createServiceRecordForCode('EMRG-FOOD', $client, '2025-06-01');

    $emrgProgram = Program::where('code', 'EMRG')->first();

    $this->actingAs(User::factory()->admin()->create());

    // Filter to EMRG only — CSBG-VITA (Goal 3) should show 0 clients
    $component = Livewire::test(NpiReport::class)
        ->set('startDate', '2025-01-01')
        ->set('endDate', '2025-12-31')
        ->set('programId', (string) $emrgProgram->id)
        ->call('generateReport');

    $reportData = $component->get('reportData');
    expect($reportData)->not->toBeNull();

    $goal3 = $reportData->firstWhere('goal_number', 3);
    expect($goal3)->not->toBeNull();

    $indicator31 = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');
    expect($indicator31)->not->toBeNull();
    expect($indicator31['unduplicated_clients'])->toBe(0);
});

it('applyPreset fiscal_year sets correct date range', function () {
    seedNpiData();
    $this->actingAs(User::factory()->admin()->create());

    $now = now();
    $expectedStart = ($now->month >= 10
        ? $now->copy()->startOfYear()->addMonths(9)
        : $now->copy()->subYear()->startOfYear()->addMonths(9))
        ->startOfMonth()
        ->format('Y-m-d');

    Livewire::test(NpiReport::class)
        ->call('applyPreset', 'fiscal_year')
        ->assertSet('startDate', $expectedStart)
        ->assertSet('endDate', $now->format('Y-m-d'));
});

it('applyPreset this_month sets correct date range', function () {
    seedNpiData();
    $this->actingAs(User::factory()->admin()->create());

    $now = now();

    Livewire::test(NpiReport::class)
        ->call('applyPreset', 'this_month')
        ->assertSet('startDate', $now->copy()->startOfMonth()->format('Y-m-d'))
        ->assertSet('endDate', $now->format('Y-m-d'));
});
