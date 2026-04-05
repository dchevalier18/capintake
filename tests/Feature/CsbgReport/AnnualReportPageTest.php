<?php

declare(strict_types=1);

use App\Filament\Pages\CsbgReportSettings;
use App\Filament\Pages\NpiReport;
use App\Filament\Resources\FederalPovertyLevelResource\Pages\ListFederalPovertyLevels;
use App\Filament\Resources\LookupCategoryResource\Pages\ListLookupCategories;
use App\Models\User;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->supervisor = User::factory()->supervisor()->create();
    $this->caseworker = User::factory()->caseworker()->create();
});

// =========================================================================
// NPI Report Page Access
// =========================================================================

it('admin can access the NPI report page', function () {
    $this->seed(\Database\Seeders\NpiSeeder::class);

    $this->actingAs($this->admin);

    Livewire::test(NpiReport::class)
        ->assertSuccessful();
});

it('supervisor can access the NPI report page', function () {
    $this->seed(\Database\Seeders\NpiSeeder::class);

    $this->actingAs($this->supervisor);

    Livewire::test(NpiReport::class)
        ->assertSuccessful();
});

// =========================================================================
// CSBG Report Settings Page Access
// =========================================================================

it('admin can access CSBG report settings', function () {
    $this->actingAs($this->admin);

    Livewire::test(CsbgReportSettings::class)
        ->assertSuccessful();
});

it('caseworker cannot access CSBG report settings page', function () {
    $this->actingAs($this->caseworker);

    // CsbgReportSettings has canAccess() check for admin role
    // Filament redirects unauthorized users to dashboard
    $response = $this->get('/admin/csbg-report-settings');
    expect($response->status())->toBeIn([302, 403]);
});

// =========================================================================
// Lookup Management Access
// =========================================================================

it('admin can access lookup management', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListLookupCategories::class)
        ->assertSuccessful();
});

it('caseworker cannot access lookup management', function () {
    $this->actingAs($this->caseworker);

    // Filament Resources redirect unauthorized users
    $response = $this->get('/admin/lookup-categories');
    expect($response->status())->toBeIn([302, 403]);
});

// =========================================================================
// FPL Guidelines Access
// =========================================================================

it('admin can access FPL guidelines', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListFederalPovertyLevels::class)
        ->assertSuccessful();
});

it('caseworker cannot access FPL guidelines', function () {
    $this->actingAs($this->caseworker);

    // Filament Resources redirect unauthorized users
    $response = $this->get('/admin/federal-poverty-levels');
    expect($response->status())->toBeIn([302, 403]);
});

// =========================================================================
// CSBG Report Settings Save
// =========================================================================

it('admin can save CSBG report settings', function () {
    $this->actingAs($this->admin);

    Livewire::test(CsbgReportSettings::class)
        ->fillForm([
            'entity_name' => 'Test Agency',
            'state' => 'PA',
            'uei' => 'ABCD12345678',
            'reporting_period' => 'oct_sep',
            'current_fiscal_year' => 2025,
            'total_csbg_allocation' => 500000,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('csbg_report_settings', [
        'entity_name' => 'Test Agency',
        'state' => 'PA',
    ]);
});
