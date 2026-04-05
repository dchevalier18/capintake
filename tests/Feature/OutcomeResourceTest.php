<?php

declare(strict_types=1);

use App\Enums\OutcomeStatus;
use App\Filament\Resources\OutcomeResource;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\FnpiTarget;
use App\Models\NpiGoal;
use App\Models\NpiIndicator;
use App\Models\Outcome;
use App\Models\Program;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Services\NpiReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function seedOutcomeTestData(): void
{
    test()->seed(\Database\Seeders\LookupSeeder::class);
    test()->seed(\Database\Seeders\NpiSeeder::class);
    test()->seed(\Database\Seeders\ProgramSeeder::class);
    test()->seed(\Database\Seeders\NpiServiceMappingSeeder::class);
}

// =============================================================================
// Outcome Resource — CRUD
// =============================================================================

it('outcome list page renders for admin', function () {
    seedOutcomeTestData();
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(OutcomeResource\Pages\ListOutcomes::class)
        ->assertSuccessful();
});

it('can create an outcome', function () {
    seedOutcomeTestData();
    $this->actingAs(User::factory()->admin()->create());

    $client = Client::factory()->create();
    $indicator = NpiIndicator::where('indicator_code', 'FNPI-1a')->first();

    Livewire::test(OutcomeResource\Pages\CreateOutcome::class)
        ->fillForm([
            'client_id' => $client->id,
            'npi_indicator_id' => $indicator->id,
            'status' => OutcomeStatus::InProgress->value,
            'target_date' => '2026-06-01',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Outcome::count())->toBe(1);
    expect(Outcome::first()->status)->toBe(OutcomeStatus::InProgress);
});

it('can edit outcome status to achieved', function () {
    seedOutcomeTestData();
    $this->actingAs(User::factory()->admin()->create());

    $client = Client::factory()->create();
    $indicator = NpiIndicator::where('indicator_code', 'FNPI-1a')->first();
    $outcome = Outcome::create([
        'client_id' => $client->id,
        'npi_indicator_id' => $indicator->id,
        'status' => OutcomeStatus::InProgress->value,
        'fiscal_year' => 2026,
    ]);

    Livewire::test(OutcomeResource\Pages\EditOutcome::class, ['record' => $outcome->id])
        ->fillForm([
            'status' => OutcomeStatus::Achieved->value,
            'achieved_date' => '2026-03-15',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $outcome->refresh();
    expect($outcome->status)->toBe(OutcomeStatus::Achieved);
    expect($outcome->achieved_date->format('Y-m-d'))->toBe('2026-03-15');
});

it('requires client_id and npi_indicator_id', function () {
    seedOutcomeTestData();
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(OutcomeResource\Pages\CreateOutcome::class)
        ->fillForm([
            'client_id' => null,
            'npi_indicator_id' => null,
            'status' => OutcomeStatus::InProgress->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['client_id', 'npi_indicator_id']);
});

it('caseworker cannot delete outcomes', function () {
    seedOutcomeTestData();
    $caseworker = User::factory()->caseworker()->create();

    $outcome = Outcome::factory()->create(['fiscal_year' => 2026]);

    $this->actingAs($caseworker);

    expect($caseworker->can('delete', $outcome))->toBeFalse();
});

// =============================================================================
// Outcome Tracking — NPI Report Integration
// =============================================================================

it('NPI report generate returns 5-column format with outcomes', function () {
    seedOutcomeTestData();

    $client = Client::factory()->create();
    $service = Service::where('code', 'CSBG-VITA')->firstOrFail();
    $program = $service->program;
    $caseworker = User::factory()->caseworker()->create();
    $enrollment = Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $program->id,
        'caseworker_id' => $caseworker->id,
    ]);

    ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'enrollment_id' => $enrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => '2025-06-01',
    ]);

    $indicator = NpiIndicator::where('indicator_code', 'FNPI-3a')->first();

    // Record an achieved outcome
    Outcome::create([
        'client_id' => $client->id,
        'npi_indicator_id' => $indicator->id,
        'enrollment_id' => $enrollment->id,
        'status' => OutcomeStatus::Achieved->value,
        'achieved_date' => '2025-06-15',
        'fiscal_year' => 2025,
    ]);

    // Set a target
    FnpiTarget::create([
        'npi_indicator_id' => $indicator->id,
        'fiscal_year' => 2026,
        'target_count' => 10,
    ]);

    $npiService = new NpiReportService();
    $report = $npiService->generate('2025-01-01', '2025-12-31');

    $goal3 = $report->firstWhere('goal_number', 3);
    $ind = collect($goal3['indicators'])->firstWhere('indicator_code', 'FNPI-3a');

    // 5-column format keys exist
    expect($ind)->toHaveKeys(['individuals_served', 'target', 'actual_results', 'pct_achieving', 'target_accuracy']);
    expect($ind['individuals_served'])->toBe(1);
    expect($ind['actual_results'])->toBe(1);
});

it('only achieved outcomes are counted in actual_results', function () {
    seedOutcomeTestData();

    $indicator = NpiIndicator::where('indicator_code', 'FNPI-1a')->first();

    // 3 outcomes: 1 achieved, 1 in_progress, 1 not_achieved
    Outcome::create([
        'client_id' => Client::factory()->create()->id,
        'npi_indicator_id' => $indicator->id,
        'status' => OutcomeStatus::Achieved->value,
        'achieved_date' => '2025-06-01',
        'fiscal_year' => 2025,
    ]);
    Outcome::create([
        'client_id' => Client::factory()->create()->id,
        'npi_indicator_id' => $indicator->id,
        'status' => OutcomeStatus::InProgress->value,
        'fiscal_year' => 2025,
    ]);
    Outcome::create([
        'client_id' => Client::factory()->create()->id,
        'npi_indicator_id' => $indicator->id,
        'status' => OutcomeStatus::NotAchieved->value,
        'fiscal_year' => 2025,
    ]);

    $npiService = new NpiReportService();
    $counts = $npiService->outcomeCountsByIndicator('2025-01-01', '2025-12-31', [$indicator->id]);

    expect($counts[$indicator->id])->toBe(1);
});

it('outcome fiscal_year is auto-computed on save', function () {
    seedOutcomeTestData();

    $outcome = Outcome::create([
        'client_id' => Client::factory()->create()->id,
        'npi_indicator_id' => NpiIndicator::first()->id,
        'status' => OutcomeStatus::Achieved->value,
        'achieved_date' => '2025-11-15', // Nov = Oct-Sep FY 2026
        'fiscal_year' => 0, // will be auto-computed
    ]);

    // Oct-Sep convention: Nov 2025 → FY 2026
    expect($outcome->fiscal_year)->toBe(2026);
});

// =============================================================================
// Age Ranges — Fixed to 10 CSBG brackets
// =============================================================================

it('NPI report uses 10 CSBG age ranges', function () {
    seedOutcomeTestData();

    $npiService = new NpiReportService();
    $rows = $npiService->toFlatRows('2025-01-01', '2025-12-31');

    $header = $rows[0];
    expect($header)->toContain('Age: 6-13');
    expect($header)->toContain('Age: 14-17');
    expect($header)->toContain('Age: 55-59');
    expect($header)->toContain('Age: 60-64');
    expect($header)->toContain('Age: 65-74');
    expect($header)->toContain('Age: 75+');
    // Old ranges should NOT be present
    expect($header)->not->toContain('Age: 6-12');
    expect($header)->not->toContain('Age: 13-17');
    expect($header)->not->toContain('Age: 55-64');
});
