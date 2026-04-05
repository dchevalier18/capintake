<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Household;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Services\CsbgReportService;
use Database\Seeders\LookupSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->seed(ProgramSeeder::class);
});

function createServedClient(array $attrs = [], string $serviceDate = '2025-06-01'): Client
{
    $client = Client::factory()->create(array_merge([
        'intake_status' => \App\Enums\IntakeStatus::Complete,
    ], $attrs));

    $service = Service::where('code', 'CSBG-CM')->first();
    $caseworker = User::factory()->caseworker()->create();
    $enrollment = Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $service->program_id,
        'caseworker_id' => $caseworker->id,
    ]);

    ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'enrollment_id' => $enrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => $serviceDate,
    ]);

    return $client;
}

// =========================================================================
// Total Unduplicated Count
// =========================================================================

it('counts total unduplicated clients served', function () {
    createServedClient(['gender' => 'male']);
    createServedClient(['gender' => 'female']);

    $report = (new CsbgReportService())->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['total_unduplicated_individuals'])->toBe(2);
});

it('deduplicates clients with multiple service records', function () {
    $client = createServedClient(['gender' => 'male']);

    // Additional service for same client
    $service = Service::where('code', 'CSBG-CM')->first();
    ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'enrollment_id' => $client->enrollments()->first()->id,
        'provided_by' => User::factory()->caseworker()->create()->id,
        'service_date' => '2025-07-01',
    ]);

    $report = (new CsbgReportService())->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['total_unduplicated_individuals'])->toBe(1);
});

// =========================================================================
// Demographic Breakdowns
// =========================================================================

it('breaks down by gender correctly', function () {
    createServedClient(['gender' => 'male']);
    createServedClient(['gender' => 'female']);
    createServedClient(['gender' => 'female']);

    $report = (new CsbgReportService())->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_gender']['male'])->toBe(1)
        ->and($report['by_gender']['female'])->toBe(2);
});

it('breaks down by race correctly', function () {
    createServedClient(['race' => 'white']);
    createServedClient(['race' => 'black_african_american']);
    createServedClient(['race' => 'white']);

    $report = (new CsbgReportService())->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_race']['white'])->toBe(2)
        ->and($report['by_race']['black_african_american'])->toBe(1);
});

it('breaks down by ethnicity correctly', function () {
    createServedClient(['ethnicity' => 'hispanic_latino']);
    createServedClient(['ethnicity' => 'not_hispanic_latino']);

    $report = (new CsbgReportService())->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_ethnicity']['hispanic_latino'])->toBe(1)
        ->and($report['by_ethnicity']['not_hispanic_latino'])->toBe(1);
});

it('breaks down by age range correctly', function () {
    createServedClient(['date_of_birth' => now()->subYears(30), 'birth_year' => now()->subYears(30)->year]);
    createServedClient(['date_of_birth' => now()->subYears(70), 'birth_year' => now()->subYears(70)->year]);

    $report = (new CsbgReportService())->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_age']['25-44'])->toBe(1)
        ->and($report['by_age']['65-74'])->toBe(1);
});

// =========================================================================
// FPL Bracket Breakdown
// =========================================================================

it('calculates FPL percentage brackets correctly', function () {
    $client50 = createServedClient();
    $client50->enrollments()->update(['fpl_percent_at_enrollment' => 45]);

    $client125 = createServedClient();
    $client125->enrollments()->update(['fpl_percent_at_enrollment' => 120]);

    $client200 = createServedClient();
    $client200->enrollments()->update(['fpl_percent_at_enrollment' => 195]);

    $report = (new CsbgReportService())->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_fpl_bracket']['0-50%'])->toBe(1)
        ->and($report['by_fpl_bracket']['101-125%'])->toBe(1)
        ->and($report['by_fpl_bracket']['176-200%'])->toBe(1);
});

it('categorizes unknown FPL for clients without enrollment data', function () {
    $client = createServedClient();
    $client->enrollments()->update(['fpl_percent_at_enrollment' => null]);

    $report = (new CsbgReportService())->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_fpl_bracket']['unknown'])->toBe(1);
});

// =========================================================================
// Housing Type Breakdown
// =========================================================================

it('breaks down by housing type correctly', function () {
    $household1 = Household::factory()->create(['housing_type' => 'own']);
    $household2 = Household::factory()->create(['housing_type' => 'rent']);

    createServedClient(['household_id' => $household1->id]);
    createServedClient(['household_id' => $household2->id]);
    createServedClient(['household_id' => $household2->id]);

    $report = (new CsbgReportService())->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_housing_type']['own'])->toBe(1)
        ->and($report['by_housing_type']['rent'])->toBe(2);
});

// =========================================================================
// Date Filtering
// =========================================================================

it('excludes clients with services outside the reporting period', function () {
    createServedClient(['gender' => 'male'], '2025-06-01');
    createServedClient(['gender' => 'female'], '2024-01-01'); // outside period

    $report = (new CsbgReportService())->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['total_unduplicated_individuals'])->toBe(1);
});
