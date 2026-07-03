<?php

declare(strict_types=1);

use App\Enums\IntakeStatus;
use App\Filament\Pages\IntakeWizard;
use App\Models\Client;
use App\Models\Household;
use App\Models\User;
use App\Services\DataQualityService;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

// ---------------------------------------------------------------------------
// Step 1 — CSBG Section C individual characteristics
// ---------------------------------------------------------------------------

it('persists section c client characteristics from step 1', function () {
    $component = Livewire::test(IntakeWizard::class)
        ->set('data.first_name', 'Charact')
        ->set('data.last_name', 'Eristics')
        ->set('data.date_of_birth', '1985-06-15')
        ->set('data.address_line_1', '1 CSBG Way')
        ->set('data.city', 'Allentown')
        ->set('data.state', 'PA')
        ->set('data.zip', '18101')
        ->set('data.education_level', 'hs_graduate')
        ->set('data.employment_status', 'employed_full')
        ->set('data.military_status', 'never_served')
        ->set('data.health_insurance_status', 'yes')
        ->set('data.health_insurance_source', 'medicaid');

    $wizard = $component->instance();
    $wizard->saveDraftStep1();

    $this->assertDatabaseHas('clients', [
        'first_name' => 'Charact',
        'education_level' => 'hs_graduate',
        'employment_status' => 'employed_full',
        'military_status' => 'never_served',
        'health_insurance_status' => 'yes',
        'health_insurance_source' => 'medicaid',
    ]);
});

it('sets the veteran flag when military status is veteran', function () {
    $component = Livewire::test(IntakeWizard::class)
        ->set('data.first_name', 'Vet')
        ->set('data.last_name', 'Eran')
        ->set('data.date_of_birth', '1970-01-01')
        ->set('data.address_line_1', '2 Service Rd')
        ->set('data.city', 'Allentown')
        ->set('data.state', 'PA')
        ->set('data.zip', '18101')
        ->set('data.military_status', 'veteran')
        ->set('data.is_veteran', false);

    $component->instance()->saveDraftStep1();

    $client = Client::where('first_name', 'Vet')->first();
    expect($client->is_veteran)->toBeTrue()
        ->and($client->military_status)->toBe('veteran');
});

it('clears the insurance source when insurance status is not yes', function () {
    $component = Livewire::test(IntakeWizard::class)
        ->set('data.first_name', 'NoIns')
        ->set('data.last_name', 'Urance')
        ->set('data.date_of_birth', '1990-02-02')
        ->set('data.address_line_1', '3 Gap St')
        ->set('data.city', 'Allentown')
        ->set('data.state', 'PA')
        ->set('data.zip', '18101')
        ->set('data.health_insurance_status', 'no')
        ->set('data.health_insurance_source', 'medicaid');

    $component->instance()->saveDraftStep1();

    $client = Client::where('first_name', 'NoIns')->first();
    expect($client->health_insurance_status)->toBe('no')
        ->and($client->health_insurance_source)->toBeNull();
});

it('saves a draft with all section c characteristics left blank', function () {
    $component = Livewire::test(IntakeWizard::class)
        ->set('data.first_name', 'Optional')
        ->set('data.last_name', 'Fields')
        ->set('data.date_of_birth', '1995-03-03')
        ->set('data.address_line_1', '4 Unknown Ln')
        ->set('data.city', 'Allentown')
        ->set('data.state', 'PA')
        ->set('data.zip', '18101');

    $component->instance()->saveDraftStep1();

    $this->assertDatabaseHas('clients', [
        'first_name' => 'Optional',
        'education_level' => null,
        'employment_status' => null,
        'military_status' => null,
        'health_insurance_status' => null,
        'intake_status' => IntakeStatus::Draft->value,
    ]);
});

// ---------------------------------------------------------------------------
// Step 2 — household type and member demographics
// ---------------------------------------------------------------------------

it('persists household type and member demographics from step 2', function () {
    $household = Household::factory()->create();
    $client = Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 2,
    ]);

    $component = Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class)
        ->set('data.household_type', 'single_parent_female')
        ->set('data.household_members', [
            [
                'first_name' => 'Kid',
                'last_name' => 'Member',
                'date_of_birth' => '2015-01-01',
                'relationship_to_client' => 'child',
                'gender' => 'female',
                'race' => 'black_african_american',
                'ethnicity' => 'not_hispanic_latino',
                'education_level' => 'grades_0_8',
                'is_disabled' => true,
            ],
        ]);

    $component->instance()->saveDraftStep2();

    expect($household->fresh()->household_type)->toBe('single_parent_female');

    $this->assertDatabaseHas('household_members', [
        'household_id' => $household->id,
        'first_name' => 'Kid',
        'race' => 'black_african_american',
        'ethnicity' => 'not_hispanic_latino',
        'education_level' => 'grades_0_8',
        'is_disabled' => true,
    ]);
});

// ---------------------------------------------------------------------------
// Step 3 — non-cash benefits
// ---------------------------------------------------------------------------

it('persists non-cash benefits from step 3', function () {
    $client = Client::factory()->create([
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 3,
    ]);

    $component = Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class)
        ->set('data.non_cash_benefits', ['snap', 'wic', 'liheap']);

    $component->instance()->saveDraftStep3();

    expect($client->nonCashBenefits()->pluck('benefit_type')->all())
        ->toEqualCanonicalizing(['snap', 'wic', 'liheap']);
});

it('replaces non-cash benefits on re-save instead of duplicating', function () {
    $client = Client::factory()->create([
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 3,
    ]);

    $component = Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class)
        ->set('data.non_cash_benefits', ['snap', 'wic']);
    $component->instance()->saveDraftStep3();

    $component->set('data.non_cash_benefits', ['liheap']);
    $component->instance()->saveDraftStep3();

    expect($client->nonCashBenefits()->pluck('benefit_type')->all())->toBe(['liheap']);
});

// ---------------------------------------------------------------------------
// Draft resume round-trips the new fields
// ---------------------------------------------------------------------------

it('loads section c characteristics when resuming a draft', function () {
    $household = Household::factory()->create(['household_type' => 'two_parent']);
    $client = Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 3,
        'education_level' => 'ged',
        'employment_status' => 'unemployed_short',
        'military_status' => 'veteran',
        'health_insurance_status' => 'yes',
        'health_insurance_source' => 'medicare',
    ]);
    $client->nonCashBenefits()->create([
        'benefit_type' => 'snap',
        'effective_date' => now(),
        'is_active' => true,
    ]);

    $component = Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class);

    expect($component->get('data.education_level'))->toBe('ged')
        ->and($component->get('data.employment_status'))->toBe('unemployed_short')
        ->and($component->get('data.military_status'))->toBe('veteran')
        ->and($component->get('data.health_insurance_status'))->toBe('yes')
        ->and($component->get('data.health_insurance_source'))->toBe('medicare')
        ->and($component->get('data.household_type'))->toBe('two_parent')
        ->and($component->get('data.non_cash_benefits'))->toBe(['snap']);
});

// ---------------------------------------------------------------------------
// Data quality household completeness
// ---------------------------------------------------------------------------

it('reports households missing section c characteristics', function () {
    $complete = Household::factory()->create([
        'household_type' => 'single_person',
        'housing_type' => 'rent',
    ]);
    $incomplete = Household::factory()->create([
        'household_type' => null,
        'housing_type' => null,
    ]);
    Client::factory()->create(['household_id' => $complete->id]);
    Client::factory()->create(['household_id' => $incomplete->id]);

    $result = (new DataQualityService)->householdCompleteness();

    expect($result['total_households'])->toBe(2)
        ->and($result['missing_household_type'])->toBe(1)
        ->and($result['missing_housing_type'])->toBe(1);
});
