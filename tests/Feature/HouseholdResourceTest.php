<?php

declare(strict_types=1);

use App\Filament\Resources\HouseholdResource\Pages\CreateHousehold;
use App\Filament\Resources\HouseholdResource\Pages\EditHousehold;
use App\Filament\Resources\HouseholdResource\Pages\ListHouseholds;
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

it('can list households for an authenticated admin', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListHouseholds::class)
        ->assertSuccessful();
});

it('can list households for a caseworker', function () {
    $this->actingAs($this->caseworker);

    Livewire::test(ListHouseholds::class)
        ->assertSuccessful();
});

// --- Create ---

it('can create a household with valid data', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateHousehold::class)
        ->fillForm([
            'address_line_1' => '123 Main St',
            'city' => 'Scranton',
            'state' => 'PA',
            'zip' => '18503',
            'county' => 'Lackawanna',
            'housing_type' => 'rent',
            'household_size' => 3,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('households', [
        'address_line_1' => '123 Main St',
        'city' => 'Scranton',
        'state' => 'PA',
        'zip' => '18503',
    ]);
});

it('caseworker can create a household', function () {
    $this->actingAs($this->caseworker);

    Livewire::test(CreateHousehold::class)
        ->fillForm([
            'address_line_1' => '456 Oak Ave',
            'city' => 'Wilkes-Barre',
            'state' => 'PA',
            'zip' => '18701',
            'household_size' => 2,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('households', [
        'address_line_1' => '456 Oak Ave',
        'city' => 'Wilkes-Barre',
    ]);
});

// --- Validation ---

it('cannot create a household with missing required fields', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateHousehold::class)
        ->fillForm([
            'address_line_1' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'household_size' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'address_line_1' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'household_size' => 'required',
        ]);
});

// --- Edit ---

it('can edit an existing household', function () {
    $household = Household::factory()->create(['address_line_1' => '789 Elm St']);

    $this->actingAs($this->admin);

    Livewire::test(EditHousehold::class, ['record' => $household->getRouteKey()])
        ->fillForm([
            'address_line_1' => '100 Updated Blvd',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('households', [
        'id' => $household->id,
        'address_line_1' => '100 Updated Blvd',
    ]);
});

// --- Delete (soft delete) ---

it('admin can delete a household', function () {
    $household = Household::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(EditHousehold::class, ['record' => $household->getRouteKey()])
        ->callAction(DeleteAction::class);

    $this->assertSoftDeleted('households', ['id' => $household->id]);
});

// --- Authorization ---

it('caseworker cannot delete a household', function () {
    $household = Household::factory()->create();

    $this->actingAs($this->caseworker);

    Livewire::test(EditHousehold::class, ['record' => $household->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});

it('supervisor cannot delete a household', function () {
    $household = Household::factory()->create();

    $this->actingAs($this->supervisor);

    Livewire::test(EditHousehold::class, ['record' => $household->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});
