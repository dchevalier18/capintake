<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Enums\IncomeFrequency;
use App\Filament\Resources\ClientResource\Pages\CreateClient;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\ClientResource\Pages\ViewClient;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\FederalPovertyLevel;
use App\Models\Household;
use App\Models\IncomeRecord;
use App\Models\Program;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\LookupSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->supervisor = User::factory()->supervisor()->create();
    $this->caseworker = User::factory()->caseworker()->create();
});

// --- List ---

it('can list clients for an authenticated admin', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListClients::class)
        ->assertSuccessful();
});

it('can list clients for a caseworker', function () {
    $this->actingAs($this->caseworker);

    Livewire::test(ListClients::class)
        ->assertSuccessful();
});

// --- Create ---

it('can create a client with valid data', function () {
    $household = Household::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(CreateClient::class)
        ->fillForm([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-05-15',
            'household_id' => $household->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('clients', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'household_id' => $household->id,
    ]);
});

it('caseworker can create a client', function () {
    $household = Household::factory()->create();

    $this->actingAs($this->caseworker);

    Livewire::test(CreateClient::class)
        ->fillForm([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'date_of_birth' => '1985-03-20',
            'household_id' => $household->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('clients', [
        'first_name' => 'John',
        'last_name' => 'Smith',
    ]);
});

// --- Validation ---

it('cannot create a client with missing required fields', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateClient::class)
        ->fillForm([
            'first_name' => '',
            'last_name' => '',
            'date_of_birth' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'first_name' => 'required',
            'last_name' => 'required',
            'date_of_birth' => 'required',
        ]);
});

// --- Edit ---

it('can edit an existing client', function () {
    $client = Client::factory()->create(['first_name' => 'Original']);

    $this->actingAs($this->admin);

    Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
        ->fillForm([
            'first_name' => 'Updated',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'first_name' => 'Updated',
    ]);
});

// --- Delete (soft delete) ---

it('admin can delete a client', function () {
    $client = Client::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
        ->callAction(DeleteAction::class);

    $this->assertSoftDeleted('clients', ['id' => $client->id]);
});

// --- Authorization ---

it('caseworker cannot delete a client', function () {
    $client = Client::factory()->create();

    $this->actingAs($this->caseworker);

    Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});

it('supervisor cannot delete a client', function () {
    $client = Client::factory()->create();

    $this->actingAs($this->supervisor);

    Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});

// --- View Page ---

it('can render the view page', function () {
    $client = Client::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(ViewClient::class, ['record' => $client->getRouteKey()])
        ->assertSuccessful();
});

it('view page displays client name', function () {
    $client = Client::factory()->create([
        'first_name' => 'TestView',
        'last_name' => 'ClientName',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ViewClient::class, ['record' => $client->getRouteKey()])
        ->assertSee('TestView')
        ->assertSee('ClientName');
});

it('view page shows household address', function () {
    $household = Household::factory()->create([
        'address_line_1' => '742 Evergreen Terrace',
        'city' => 'Springfield',
        'state' => 'IL',
        'zip' => '62704',
    ]);

    $client = Client::factory()->create(['household_id' => $household->id]);

    $this->actingAs($this->admin);

    Livewire::test(ViewClient::class, ['record' => $client->getRouteKey()])
        ->assertSee('742 Evergreen Terrace');
});

it('view page shows active enrollments', function () {
    $client = Client::factory()->create();
    $program = Program::factory()->create(['name' => 'CSBG Test Program']);

    Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $program->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ViewClient::class, ['record' => $client->getRouteKey()])
        ->assertSee('CSBG Test Program');
});

it('can record a service via header action', function () {
    $client = Client::factory()->create();
    $program = Program::factory()->create();
    $service = Service::factory()->create(['program_id' => $program->id]);
    $enrollment = Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $program->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ViewClient::class, ['record' => $client->getRouteKey()])
        ->callAction('recordService', [
            'enrollment_id' => $enrollment->id,
            'service_id' => $service->id,
            'service_date' => now()->format('Y-m-d'),
            'quantity' => 1,
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('service_records', [
        'client_id' => $client->id,
        'enrollment_id' => $enrollment->id,
        'service_id' => $service->id,
    ]);
});

it('can create a new enrollment via header action', function () {
    $client = Client::factory()->create();
    $program = Program::factory()->create();

    FederalPovertyLevel::create([
        'year' => now()->year,
        'household_size' => $client->household->household_size,
        'poverty_guideline' => 15060,
        'region' => 'continental',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ViewClient::class, ['record' => $client->getRouteKey()])
        ->callAction('newEnrollment', [
            'program_id' => $program->id,
            'enrolled_at' => now()->format('Y-m-d'),
            'status' => EnrollmentStatus::Active->value,
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('enrollments', [
        'client_id' => $client->id,
        'program_id' => $program->id,
    ]);
});

it('can update income via header action', function () {
    $client = Client::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(ViewClient::class, ['record' => $client->getRouteKey()])
        ->callAction('updateIncome', [
            'source' => 'employment',
            'amount' => 2500,
            'frequency' => IncomeFrequency::Monthly->value,
            'effective_date' => now()->format('Y-m-d'),
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('income_records', [
        'client_id' => $client->id,
        'source' => 'employment',
    ]);
});

it('list page shows active programs for clients', function () {
    $client = Client::factory()->create();
    $program = Program::factory()->create(['name' => 'Visible Program']);

    Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $program->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ListClients::class)
        ->assertSee('Visible Program');
});

it('list page has view action instead of edit', function () {
    $client = Client::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(ListClients::class)
        ->assertTableActionExists('view');
});
