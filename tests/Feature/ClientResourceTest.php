<?php

declare(strict_types=1);

use App\Filament\Resources\ClientResource\Pages\CreateClient;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Models\Client;
use App\Models\Household;
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
