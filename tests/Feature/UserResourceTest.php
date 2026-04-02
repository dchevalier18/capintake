<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\AgencySetting;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    AgencySetting::create(['agency_name' => 'Test Agency', 'setup_completed' => true]);
    $this->admin = User::factory()->admin()->create();
    $this->supervisor = User::factory()->supervisor()->create();
    $this->caseworker = User::factory()->caseworker()->create();
});

// --- List ---

it('admin can list users', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertSuccessful();
});

it('supervisor can list users', function () {
    $this->actingAs($this->supervisor);

    Livewire::test(ListUsers::class)
        ->assertSuccessful();
});

it('caseworker cannot access user list', function () {
    $this->actingAs($this->caseworker);

    $this->get('/admin/users')
        ->assertForbidden();
});

// --- Create ---

it('admin can create a user with valid data', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New User',
            'email' => 'newuser@capintake.test',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
            'role' => UserRole::Caseworker->value,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@capintake.test',
        'role' => UserRole::Caseworker->value,
    ]);
});

it('supervisor cannot create a user', function () {
    $this->actingAs($this->supervisor);

    $this->get('/admin/users/create')
        ->assertForbidden();
});

it('cannot create user with weak password', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Weak Pass',
            'email' => 'weak@capintake.test',
            'password' => 'short',
            'password_confirmation' => 'short',
            'role' => UserRole::Caseworker->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['password']);
});

it('cannot create user with duplicate email', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Duplicate',
            'email' => $this->admin->email,
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
            'role' => UserRole::Caseworker->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);
});

// --- Edit ---

it('admin can edit a user', function () {
    $user = User::factory()->caseworker()->create(['name' => 'Original Name', 'phone' => '5551234567']);

    $this->actingAs($this->admin);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
    ]);
});

it('admin can deactivate a user', function () {
    $user = User::factory()->caseworker()->create(['is_active' => true]);

    $this->actingAs($this->admin);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'is_active' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_active' => false,
    ]);
});

it('supervisor cannot edit a user', function () {
    $user = User::factory()->caseworker()->create();

    $this->actingAs($this->supervisor);

    $this->get("/admin/users/{$user->getRouteKey()}/edit")
        ->assertForbidden();
});

// --- Delete (soft delete) ---

it('admin can delete a user', function () {
    $user = User::factory()->caseworker()->create();

    $this->actingAs($this->admin);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->callAction(DeleteAction::class);

    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

it('admin cannot delete themselves', function () {
    $this->actingAs($this->admin);

    Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});
