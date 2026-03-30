<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Enums\IncomeFrequency;
use App\Enums\IntakeStatus;
use App\Filament\Pages\IntakeWizard;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\FederalPovertyLevelSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->caseworker = User::factory()->caseworker()->create();
});

// ---------------------------------------------------------------------------
// 1. Page loads for authenticated users
// ---------------------------------------------------------------------------

it('loads the intake wizard page for an authenticated admin', function () {
    $this->actingAs($this->admin);

    Livewire::test(IntakeWizard::class)
        ->assertSuccessful();
});

it('loads the intake wizard page for a caseworker', function () {
    $this->actingAs($this->caseworker);

    Livewire::test(IntakeWizard::class)
        ->assertSuccessful();
});

// ---------------------------------------------------------------------------
// 2. Wizard renders with default data
// ---------------------------------------------------------------------------

it('renders the wizard with default form values', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(IntakeWizard::class)
        ->assertSuccessful();

    expect($component->get('data.preferred_language'))->toBe('en')
        ->and($component->get('data.state'))->toBe('PA')
        ->and($component->get('data.is_head_of_household'))->toBeTrue()
        ->and($component->get('data.relationship_to_head'))->toBe('self')
        ->and($component->get('data.household_mode'))->toBe('new');
});

// ---------------------------------------------------------------------------
// 3. Full intake flow — create draft, then submit to complete
// ---------------------------------------------------------------------------

it('completes a full intake flow and creates all records', function () {
    $this->seed(FederalPovertyLevelSeeder::class);
    $this->seed(ProgramSeeder::class);

    $this->actingAs($this->admin);

    $program = Program::where('code', 'CSBG')->first();

    // Simulate the wizard's step-by-step draft saving by creating
    // the records directly, as the afterValidation callbacks would.
    $household = Household::create([
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4',
        'city' => 'Harrisburg',
        'state' => 'PA',
        'zip' => '17101',
        'county' => 'Dauphin',
        'housing_type' => 'rented',
        'household_size' => 2,
    ]);

    $client = Client::create([
        'household_id' => $household->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'middle_name' => 'M',
        'date_of_birth' => '1985-06-15',
        'phone' => '(717) 555-1234',
        'email' => 'john@example.com',
        'gender' => 'male',
        'race' => 'white',
        'ethnicity' => 'not_hispanic',
        'is_veteran' => false,
        'is_disabled' => false,
        'is_head_of_household' => true,
        'preferred_language' => 'en',
        'relationship_to_head' => 'self',
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 5,
    ]);

    HouseholdMember::create([
        'household_id' => $household->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'date_of_birth' => '1987-03-20',
        'relationship_to_client' => 'spouse',
    ]);

    IncomeRecord::create([
        'client_id' => $client->id,
        'source' => 'employment',
        'source_description' => 'Acme Corp',
        'amount' => 1500.00,
        'frequency' => IncomeFrequency::Monthly,
        'effective_date' => now(),
        'is_verified' => false,
    ]);

    Enrollment::create([
        'client_id' => $client->id,
        'program_id' => $program->id,
        'caseworker_id' => $this->admin->id,
        'status' => EnrollmentStatus::Pending,
        'enrolled_at' => now()->format('Y-m-d'),
        'household_income_at_enrollment' => 18000.00,
        'household_size_at_enrollment' => 2,
        'fpl_percent_at_enrollment' => 85,
        'income_eligible' => true,
    ]);

    // Mount the wizard with the draft client via query param, then submit.
    // The mount() method reads request()->query('client'), so we use withQueryParams.
    Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class)
        ->call('submit');

    // Verify the client is marked as complete
    $client->refresh();
    expect($client->intake_status)->toBe(IntakeStatus::Complete)
        ->and($client->intake_step)->toBe(5);

    // Verify enrollment was activated
    $enrollment = $client->enrollments()->first();
    expect($enrollment->status)->toBe(EnrollmentStatus::Active);

    // Verify all related records exist
    expect($client->household)->not->toBeNull()
        ->and($client->household->members)->toHaveCount(1)
        ->and($client->incomeRecords)->toHaveCount(1)
        ->and($client->enrollments)->toHaveCount(1);
});

// ---------------------------------------------------------------------------
// 4. Submit marks client complete and activates enrollments
// ---------------------------------------------------------------------------

it('marks the client as complete on submit', function () {
    $this->actingAs($this->admin);

    $household = Household::factory()->create();
    $client = Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 5,
    ]);

    Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class)
        ->call('submit');

    $client->refresh();
    expect($client->intake_status)->toBe(IntakeStatus::Complete);
});

it('activates all pending enrollments on submit', function () {
    $this->seed(ProgramSeeder::class);
    $this->actingAs($this->admin);

    $household = Household::factory()->create();
    $client = Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 5,
    ]);

    $program = Program::first();
    Enrollment::create([
        'client_id' => $client->id,
        'program_id' => $program->id,
        'caseworker_id' => $this->admin->id,
        'status' => EnrollmentStatus::Pending,
        'enrolled_at' => now(),
        'household_income_at_enrollment' => 18000,
        'household_size_at_enrollment' => 1,
        'fpl_percent_at_enrollment' => 100,
        'income_eligible' => true,
    ]);

    Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class)
        ->call('submit');

    $enrollment = Enrollment::where('client_id', $client->id)->first();
    expect($enrollment->status)->toBe(EnrollmentStatus::Active);
});

// ---------------------------------------------------------------------------
// 5. Duplicate detection
// ---------------------------------------------------------------------------

it('detects duplicate clients by name and date of birth', function () {
    $this->actingAs($this->admin);

    // Create an existing completed client
    $existingHousehold = Household::factory()->create();
    Client::factory()->create([
        'household_id' => $existingHousehold->id,
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'date_of_birth' => '1990-01-15',
        'intake_status' => IntakeStatus::Complete,
    ]);

    // Start a new wizard and set data that matches
    $component = Livewire::test(IntakeWizard::class)
        ->set('data.first_name', 'Alice')
        ->set('data.last_name', 'Smith')
        ->set('data.date_of_birth', '1990-01-15')
        ->set('data.address_line_1', '123 Test St')
        ->set('data.city', 'Harrisburg')
        ->set('data.state', 'PA')
        ->set('data.zip', '17101');

    $wizard = $component->instance();
    $reflection = new ReflectionMethod($wizard, 'checkDuplicates');
    $reflection->setAccessible(true);

    try {
        $reflection->invoke($wizard);
    } catch (\Filament\Support\Exceptions\Halt) {
        // Expected when duplicates found without acknowledgement
    }

    expect($wizard->duplicateWarning)->not->toBeNull()
        ->and($wizard->duplicateWarning)->toContain('Alice');
});

it('clears duplicate warning when no matches found', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(IntakeWizard::class)
        ->set('data.first_name', 'UniqueFirstName')
        ->set('data.last_name', 'UniqueLastName')
        ->set('data.date_of_birth', '1992-07-22');

    $wizard = $component->instance();
    $reflection = new ReflectionMethod($wizard, 'checkDuplicates');
    $reflection->setAccessible(true);
    $reflection->invoke($wizard);

    expect($wizard->duplicateWarning)->toBeNull();
});

it('detects duplicate clients by SSN last four', function () {
    $this->actingAs($this->admin);

    $existingHousehold = Household::factory()->create();
    Client::factory()->create([
        'household_id' => $existingHousehold->id,
        'first_name' => 'Bob',
        'last_name' => 'Jones',
        'ssn_last_four' => '1234',
        'intake_status' => IntakeStatus::Complete,
    ]);

    $component = Livewire::test(IntakeWizard::class)
        ->set('data.first_name', 'Different')
        ->set('data.last_name', 'Name')
        ->set('data.date_of_birth', '1980-01-01')
        ->set('data.ssn_encrypted', '999-88-1234');

    $wizard = $component->instance();
    $reflection = new ReflectionMethod($wizard, 'checkDuplicates');
    $reflection->setAccessible(true);

    try {
        $reflection->invoke($wizard);
    } catch (\Filament\Support\Exceptions\Halt) {
        // Expected
    }

    expect($wizard->duplicateWarning)->not->toBeNull()
        ->and($wizard->duplicateWarning)->toContain('Bob');
});

// ---------------------------------------------------------------------------
// 6. Draft save and resume
// ---------------------------------------------------------------------------

it('saves a draft client during step 1', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(IntakeWizard::class)
        ->set('data.first_name', 'DraftTest')
        ->set('data.last_name', 'User')
        ->set('data.date_of_birth', '1980-05-10')
        ->set('data.address_line_1', '456 Draft Ave')
        ->set('data.city', 'Pittsburgh')
        ->set('data.state', 'PA')
        ->set('data.zip', '15201');

    $wizard = $component->instance();
    $reflection = new ReflectionMethod($wizard, 'saveDraftStep1');
    $reflection->setAccessible(true);
    $reflection->invoke($wizard);

    $this->assertDatabaseHas('clients', [
        'first_name' => 'DraftTest',
        'last_name' => 'User',
        'intake_status' => IntakeStatus::Draft->value,
    ]);

    expect($wizard->clientId)->not->toBeNull();
});

it('resumes a draft intake when mounted with client query parameter', function () {
    $this->actingAs($this->admin);

    $household = Household::factory()->create([
        'address_line_1' => '789 Resume Lane',
        'city' => 'Philadelphia',
        'state' => 'PA',
        'zip' => '19101',
    ]);

    $client = Client::factory()->create([
        'household_id' => $household->id,
        'first_name' => 'ResumeTest',
        'last_name' => 'Client',
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 2,
    ]);

    $component = Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class);

    expect($component->get('clientId'))->toBe($client->id)
        ->and($component->get('data.first_name'))->toBe('ResumeTest')
        ->and($component->get('data.last_name'))->toBe('Client')
        ->and($component->get('data.address_line_1'))->toBe('789 Resume Lane');
});

it('does not load a completed client when mounting with client query parameter', function () {
    $this->actingAs($this->admin);

    $household = Household::factory()->create();
    $client = Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Complete,
    ]);

    $component = Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class);

    expect($component->get('clientId'))->toBeNull();
});

// ---------------------------------------------------------------------------
// 7. Income calculation
// ---------------------------------------------------------------------------

it('calculates total income correctly for monthly frequency', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(IntakeWizard::class);
    $wizard = $component->instance();

    $reflection = new ReflectionMethod($wizard, 'calculateTotalIncome');
    $reflection->setAccessible(true);

    $sources = [
        ['amount' => '1500', 'frequency' => 'monthly'],
    ];

    $total = $reflection->invoke($wizard, $sources);
    expect($total)->toBe(18000.00);
});

it('calculates total income correctly for multiple sources', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(IntakeWizard::class);
    $wizard = $component->instance();

    $reflection = new ReflectionMethod($wizard, 'calculateTotalIncome');
    $reflection->setAccessible(true);

    $sources = [
        ['amount' => '1500', 'frequency' => 'monthly'],   // 18000
        ['amount' => '500', 'frequency' => 'biweekly'],    // 13000
        ['amount' => '1000', 'frequency' => 'annually'],   // 1000
    ];

    $total = $reflection->invoke($wizard, $sources);
    expect($total)->toBe(32000.00);
});

it('calculates total income correctly with weekly frequency', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(IntakeWizard::class);
    $wizard = $component->instance();

    $reflection = new ReflectionMethod($wizard, 'calculateTotalIncome');
    $reflection->setAccessible(true);

    $sources = [
        ['amount' => '200', 'frequency' => 'weekly'],
    ];

    $total = $reflection->invoke($wizard, $sources);
    expect($total)->toBe(10400.00);
});

it('handles zero income sources gracefully', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(IntakeWizard::class);
    $wizard = $component->instance();

    $reflection = new ReflectionMethod($wizard, 'calculateTotalIncome');
    $reflection->setAccessible(true);

    $total = $reflection->invoke($wizard, []);
    expect($total)->toBe(0.00);
});

it('handles income sources without a frequency', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(IntakeWizard::class);
    $wizard = $component->instance();

    $reflection = new ReflectionMethod($wizard, 'calculateTotalIncome');
    $reflection->setAccessible(true);

    $sources = [
        ['amount' => '5000', 'frequency' => null],
    ];

    $total = $reflection->invoke($wizard, $sources);
    expect($total)->toBe(5000.00);
});

it('calculates total income with one_time frequency', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(IntakeWizard::class);
    $wizard = $component->instance();

    $reflection = new ReflectionMethod($wizard, 'calculateTotalIncome');
    $reflection->setAccessible(true);

    $sources = [
        ['amount' => '3000', 'frequency' => 'one_time'],
    ];

    // one_time has annualMultiplier of 1
    $total = $reflection->invoke($wizard, $sources);
    expect($total)->toBe(3000.00);
});

// ---------------------------------------------------------------------------
// 8. Draft clients do not appear in ClientResource list
// ---------------------------------------------------------------------------

it('does not show draft clients in the client resource list', function () {
    $this->actingAs($this->admin);

    $household = Household::factory()->create();
    $draftClient = Client::factory()->create([
        'household_id' => $household->id,
        'first_name' => 'DraftOnly',
        'last_name' => 'Person',
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 1,
    ]);

    $completeClient = Client::factory()->create([
        'household_id' => $household->id,
        'first_name' => 'Complete',
        'last_name' => 'Person',
        'intake_status' => IntakeStatus::Complete,
    ]);

    Livewire::test(ListClients::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$completeClient])
        ->assertCanNotSeeTableRecords([$draftClient]);
});

// ---------------------------------------------------------------------------
// 9. getDraftClients returns only draft clients
// ---------------------------------------------------------------------------

it('returns draft clients via getDraftClients', function () {
    $this->actingAs($this->admin);

    $household = Household::factory()->create();

    $draftClient = Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 2,
    ]);

    Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Complete,
    ]);

    $component = Livewire::test(IntakeWizard::class);
    $wizard = $component->instance();

    $drafts = $wizard->getDraftClients();

    expect($drafts)->toHaveCount(1)
        ->and($drafts->first()->id)->toBe($draftClient->id);
});

// ---------------------------------------------------------------------------
// 10. Step-by-step draft saving
// ---------------------------------------------------------------------------

it('saves household members during step 2', function () {
    $this->actingAs($this->admin);

    $household = Household::factory()->create();
    $client = Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 2,
    ]);

    $component = Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class)
        ->set('data.household_mode', 'new')
        ->set('data.housing_type', 'rented')
        ->set('data.is_head_of_household', true)
        ->set('data.household_members', [
            [
                'first_name' => 'ChildFirst',
                'last_name' => 'ChildLast',
                'date_of_birth' => '2015-03-10',
                'relationship_to_client' => 'child',
            ],
        ]);

    $wizard = $component->instance();
    $reflection = new ReflectionMethod($wizard, 'saveDraftStep2');
    $reflection->setAccessible(true);
    $reflection->invoke($wizard);

    $this->assertDatabaseHas('household_members', [
        'household_id' => $household->id,
        'first_name' => 'ChildFirst',
        'last_name' => 'ChildLast',
        'relationship_to_client' => 'child',
    ]);

    $client->refresh();
    expect($client->intake_step)->toBe(3);
});

it('saves income records during step 3', function () {
    $this->actingAs($this->admin);

    $household = Household::factory()->create();
    $client = Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 3,
    ]);

    $component = Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class)
        ->set('data.income_sources', [
            [
                'source' => 'employment',
                'source_description' => 'Acme Corp',
                'amount' => '2000',
                'frequency' => 'monthly',
            ],
        ]);

    $wizard = $component->instance();
    $reflection = new ReflectionMethod($wizard, 'saveDraftStep3');
    $reflection->setAccessible(true);
    $reflection->invoke($wizard);

    $this->assertDatabaseHas('income_records', [
        'client_id' => $client->id,
        'source' => 'employment',
        'source_description' => 'Acme Corp',
    ]);

    $client->refresh();
    expect($client->intake_step)->toBe(4);
});

it('saves program enrollments during step 4', function () {
    $this->seed(FederalPovertyLevelSeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->actingAs($this->admin);

    $household = Household::factory()->create();
    $client = Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 4,
    ]);

    $program = Program::where('code', 'CSBG')->first();

    $component = Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class)
        ->set('data.income_sources', [
            [
                'source' => 'employment',
                'amount' => '1000',
                'frequency' => 'monthly',
            ],
        ])
        ->set('data.household_members', [])
        ->set('data.program_enrollments', [
            [
                'program_id' => (string) $program->id,
                'enrolled_at' => now()->format('Y-m-d'),
                'caseworker_id' => (string) $this->admin->id,
            ],
        ]);

    $wizard = $component->instance();
    $reflection = new ReflectionMethod($wizard, 'saveDraftStep4');
    $reflection->setAccessible(true);
    $reflection->invoke($wizard);

    $this->assertDatabaseHas('enrollments', [
        'client_id' => $client->id,
        'program_id' => $program->id,
        'caseworker_id' => $this->admin->id,
        'status' => EnrollmentStatus::Pending->value,
    ]);

    $client->refresh();
    expect($client->intake_step)->toBe(5);
});

// ---------------------------------------------------------------------------
// 11. Submit without clientId returns early
// ---------------------------------------------------------------------------

it('does not crash when submit is called without a client', function () {
    $this->actingAs($this->admin);

    $household = Household::factory()->create();
    $client = Client::factory()->create([
        'household_id' => $household->id,
        'intake_status' => IntakeStatus::Draft,
        'intake_step' => 5,
    ]);

    $component = Livewire::withQueryParams(['client' => $client->id])
        ->test(IntakeWizard::class);

    // Artificially clear clientId to test the guard clause
    $component->set('clientId', null);

    // submit calls form->getState() first which may throw validation,
    // then checks clientId. Either way, the client stays as draft.
    try {
        $component->call('submit');
    } catch (\Illuminate\Validation\ValidationException) {
        // Expected if form validation fails before reaching the clientId check
    }

    $client->refresh();
    expect($client->intake_status)->toBe(IntakeStatus::Draft);
});

// ---------------------------------------------------------------------------
// 12. Draft step 1 updates existing draft on re-save
// ---------------------------------------------------------------------------

it('updates an existing draft client on re-save of step 1', function () {
    $this->actingAs($this->admin);

    // First save
    $component = Livewire::test(IntakeWizard::class)
        ->set('data.first_name', 'Original')
        ->set('data.last_name', 'Name')
        ->set('data.date_of_birth', '1980-05-10')
        ->set('data.address_line_1', '100 First St')
        ->set('data.city', 'York')
        ->set('data.state', 'PA')
        ->set('data.zip', '17401');

    $wizard = $component->instance();
    $reflection = new ReflectionMethod($wizard, 'saveDraftStep1');
    $reflection->setAccessible(true);
    $reflection->invoke($wizard);

    $clientId = $wizard->clientId;
    expect($clientId)->not->toBeNull();

    // Update the data and save again — set directly on the instance's data
    // array since the reflection call reads $this->data on the component.
    $wizard->data['first_name'] = 'Updated';
    $reflection->invoke($wizard);

    // Should still be the same client ID
    expect($wizard->clientId)->toBe($clientId);

    // But with updated name
    $this->assertDatabaseHas('clients', [
        'id' => $clientId,
        'first_name' => 'Updated',
    ]);

    // Only one client should exist
    expect(Client::count())->toBe(1);
});
