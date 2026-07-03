<?php

declare(strict_types=1);

use App\Enums\IntakeStatus;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
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
        'intake_status' => IntakeStatus::Complete,
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

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

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

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['total_unduplicated_individuals'])->toBe(1);
});

// =========================================================================
// Demographic Breakdowns
// =========================================================================

it('breaks down by gender correctly', function () {
    createServedClient(['gender' => 'male']);
    createServedClient(['gender' => 'female']);
    createServedClient(['gender' => 'female']);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_gender']['male'])->toBe(1)
        ->and($report['by_gender']['female'])->toBe(2);
});

it('breaks down by race correctly', function () {
    createServedClient(['race' => 'white']);
    createServedClient(['race' => 'black_african_american']);
    createServedClient(['race' => 'white']);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_race']['white'])->toBe(2)
        ->and($report['by_race']['black_african_american'])->toBe(1);
});

it('breaks down by ethnicity correctly', function () {
    createServedClient(['ethnicity' => 'hispanic_latino']);
    createServedClient(['ethnicity' => 'not_hispanic_latino']);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_ethnicity']['hispanic_latino'])->toBe(1)
        ->and($report['by_ethnicity']['not_hispanic_latino'])->toBe(1);
});

it('breaks down by age range correctly', function () {
    createServedClient(['date_of_birth' => now()->subYears(30), 'birth_year' => now()->subYears(30)->year]);
    createServedClient(['date_of_birth' => now()->subYears(70), 'birth_year' => now()->subYears(70)->year]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

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

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_fpl_bracket']['up_to_50%'])->toBe(1)
        ->and($report['by_fpl_bracket']['101-125%'])->toBe(1)
        ->and($report['by_fpl_bracket']['176-200%'])->toBe(1);
});

it('splits the official 201-250 and 251+ FPL bands', function () {
    $client225 = createServedClient();
    $client225->enrollments()->update(['fpl_percent_at_enrollment' => 225]);

    $client300 = createServedClient();
    $client300->enrollments()->update(['fpl_percent_at_enrollment' => 300]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_fpl_bracket']['201-250%'])->toBe(1)
        ->and($report['by_fpl_bracket']['251%+'])->toBe(1);
});

it('counts each household once in FPL bands even with multiple enrollments', function () {
    $client = createServedClient();
    $client->enrollments()->update(['fpl_percent_at_enrollment' => 60, 'enrolled_at' => '2025-03-01']);

    // A second, later enrollment for the same client/household
    $service = Service::where('code', 'CSBG-CM')->first();
    Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $service->program_id,
        'caseworker_id' => User::factory()->caseworker()->create()->id,
        'enrolled_at' => '2025-08-01',
        'fpl_percent_at_enrollment' => 130,
    ]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    $total = array_sum($report['by_fpl_bracket']);
    expect($total)->toBe(1)
        ->and($report['by_fpl_bracket']['126-150%'])->toBe(1);
});

it('categorizes unknown FPL for clients without enrollment data', function () {
    $client = createServedClient();
    $client->enrollments()->update(['fpl_percent_at_enrollment' => null]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_fpl_bracket']['unknown'])->toBe(1);
});

// =========================================================================
// Housing Type Breakdown
// =========================================================================

it('breaks down housing tenure by households, not individuals', function () {
    $household1 = Household::factory()->create(['housing_type' => 'own']);
    $household2 = Household::factory()->create(['housing_type' => 'rent']);

    createServedClient(['household_id' => $household1->id]);
    createServedClient(['household_id' => $household2->id]);
    createServedClient(['household_id' => $household2->id]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    // Item 11 counts households: the rent household has two clients but counts once
    expect($report['by_housing_type']['own'])->toBe(1)
        ->and($report['by_housing_type']['rent'])->toBe(1);
});

// =========================================================================
// Date Filtering
// =========================================================================

it('excludes clients with services outside the reporting period', function () {
    createServedClient(['gender' => 'male'], '2025-06-01');
    createServedClient(['gender' => 'female'], '2024-01-01'); // outside period

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['total_unduplicated_individuals'])->toBe(1);
});

// =========================================================================
// Household members count as individuals ("all individuals" per instrument)
// =========================================================================

it('counts household members as individuals in section c', function () {
    $client = createServedClient(['gender' => 'female', 'race' => 'white']);

    HouseholdMember::factory()->create([
        'household_id' => $client->household_id,
        'gender' => 'male',
        'race' => 'asian',
        'is_disabled' => false,
    ]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['total_unduplicated_individuals'])->toBe(2)
        ->and($report['by_gender']['male'])->toBe(1)
        ->and($report['by_gender']['female'])->toBe(1)
        ->and($report['by_race']['asian'])->toBe(1)
        ->and($report['total_unduplicated_households'])->toBe(1);
});

it('does not count members of households that were not served in the period', function () {
    $client = createServedClient([], '2024-01-01'); // outside the period

    HouseholdMember::factory()->create([
        'household_id' => $client->household_id,
    ]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['total_unduplicated_individuals'])->toBe(0);
});

// =========================================================================
// Age at reporting period end (not "today")
// =========================================================================

it('computes age bands as of the reporting period end year', function () {
    // Born 1990 → age 35 during a 2025 report even when "today" is later
    createServedClient(['date_of_birth' => '1990-06-01', 'birth_year' => 1990]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_age']['25-44'])->toBe(1);

    // The same client in a much later reporting year lands in an older band
    $service = Service::where('code', 'CSBG-CM')->first();
    $client = Client::first();
    ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'enrollment_id' => $client->enrollments()->first()->id,
        'provided_by' => User::factory()->caseworker()->create()->id,
        'service_date' => '2036-06-01',
    ]);

    $laterReport = (new CsbgReportService)->module4SectionC('2036-01-01', '2036-12-31');

    expect($laterReport['by_age']['45-54'])->toBe(1);
});

// =========================================================================
// Work status is limited to individuals 18+
// =========================================================================

it('excludes minors from the work status breakdown', function () {
    createServedClient([
        'date_of_birth' => '2015-01-01',
        'birth_year' => 2015,
        'employment_status' => 'employed_full', // bad data; must not count
    ]);
    createServedClient([
        'date_of_birth' => '1980-01-01',
        'birth_year' => 1980,
        'employment_status' => 'employed_full',
    ]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_employment_status']['employed_full'])->toBe(1);
});

// =========================================================================
// Item 13: nine-way income source composite
// =========================================================================

it('classifies households into the nine official income source composites', function () {
    // employment only
    $c1 = createServedClient();
    $c1->incomeRecords()->create(['source' => 'employment', 'amount' => 2000, 'frequency' => 'monthly', 'effective_date' => now()]);

    // employment + other + non-cash
    $c2 = createServedClient();
    $c2->incomeRecords()->create(['source' => 'employment', 'amount' => 1500, 'frequency' => 'monthly', 'effective_date' => now()]);
    $c2->incomeRecords()->create(['source' => 'child_support', 'amount' => 300, 'frequency' => 'monthly', 'effective_date' => now()]);
    $c2->nonCashBenefits()->create(['benefit_type' => 'snap', 'is_active' => true, 'effective_date' => now()]);

    // employment + non-cash
    $c3 = createServedClient();
    $c3->incomeRecords()->create(['source' => 'employment', 'amount' => 1500, 'frequency' => 'monthly', 'effective_date' => now()]);
    $c3->nonCashBenefits()->create(['benefit_type' => 'wic', 'is_active' => true, 'effective_date' => now()]);

    // other + non-cash
    $c4 = createServedClient();
    $c4->incomeRecords()->create(['source' => 'ssi', 'amount' => 900, 'frequency' => 'monthly', 'effective_date' => now()]);
    $c4->nonCashBenefits()->create(['benefit_type' => 'liheap', 'is_active' => true, 'effective_date' => now()]);

    // non-cash only
    $c5 = createServedClient();
    $c5->nonCashBenefits()->create(['benefit_type' => 'snap', 'is_active' => true, 'effective_date' => now()]);

    // no records at all → unknown (No Income needs an affirmative declaration)
    createServedClient();

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');
    $composite = $report['by_income_source_composite'];

    expect($composite['employment_only'])->toBe(1)
        ->and($composite['employment_other_and_noncash'])->toBe(1)
        ->and($composite['employment_and_noncash'])->toBe(1)
        ->and($composite['other_and_noncash'])->toBe(1)
        ->and($composite['noncash_only'])->toBe(1)
        ->and($composite['unknown'])->toBe(1)
        ->and($composite['no_income'])->toBe(0);
});

it('counts household member income in the composite classification', function () {
    $client = createServedClient();
    $member = HouseholdMember::factory()->create([
        'household_id' => $client->household_id,
    ]);
    IncomeRecord::create([
        'household_member_id' => $member->id,
        'source' => 'employment',
        'amount' => 1200,
        'frequency' => 'monthly',
        'effective_date' => now(),
    ]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_income_source_composite']['employment_only'])->toBe(1);
});

// =========================================================================
// Items 14/15 count households
// =========================================================================

it('counts households, not individuals, for other income sources and benefits', function () {
    $household = Household::factory()->create();
    $clientA = createServedClient(['household_id' => $household->id]);
    $clientB = createServedClient(['household_id' => $household->id]);

    foreach ([$clientA, $clientB] as $client) {
        $client->incomeRecords()->create(['source' => 'ssi', 'amount' => 900, 'frequency' => 'monthly', 'effective_date' => now()]);
        $client->nonCashBenefits()->create(['benefit_type' => 'snap', 'is_active' => true, 'effective_date' => now()]);
    }

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_income_source_type']['ssi'])->toBe(1)
        ->and($report['by_non_cash_benefit']['snap'])->toBe(1);
});

it('excludes employment sources from the other income source table', function () {
    $client = createServedClient();
    $client->incomeRecords()->create(['source' => 'employment', 'amount' => 2000, 'frequency' => 'monthly', 'effective_date' => now()]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_income_source_type'])->not->toHaveKey('employment')
        ->and($report['by_income_source_type'])->not->toHaveKey('self_employment');
});

// =========================================================================
// Sections E/F: per-program unduplicated counts
// =========================================================================

it('reports unduplicated individuals and households per program', function () {
    $household = Household::factory()->create();
    createServedClient(['household_id' => $household->id]);
    createServedClient(['household_id' => $household->id]);
    createServedClient();

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    $sectionE = collect($report['section_e']);
    $sectionF = collect($report['section_f']);

    expect($sectionE->firstWhere('program', 'Community Services Block Grant')['count'] ?? $sectionE->first()['count'])->toBe(3)
        ->and($sectionF->first()['count'])->toBe(2);
});

// =========================================================================
// Zero-filled official rows
// =========================================================================

it('includes every official category with a zero count when no one matches', function () {
    createServedClient(['gender' => 'male']);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    // Every seeded lookup value appears even at 0, with unknown last
    expect($report['by_gender'])->toHaveKey('female')
        ->and($report['by_gender']['female'])->toBe(0)
        ->and(array_key_last($report['by_gender']))->toBe('unknown')
        ->and($report['by_military_status'])->toHaveKey('active')
        ->and($report['by_household_size'])->toHaveKey('6+')
        ->and($report['by_fpl_bracket'])->toHaveKey('201-250%');
});

// =========================================================================
// Disabling condition (item 5a)
// =========================================================================

it('breaks down disabling condition from the disability flag', function () {
    createServedClient(['is_disabled' => true]);
    createServedClient(['is_disabled' => false]);

    $report = (new CsbgReportService)->module4SectionC('2025-01-01', '2025-12-31');

    expect($report['by_disabling_condition']['yes'])->toBe(1)
        ->and($report['by_disabling_condition']['no'])->toBe(1);
});
