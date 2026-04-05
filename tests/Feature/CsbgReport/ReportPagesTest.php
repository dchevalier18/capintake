<?php

declare(strict_types=1);

use App\Filament\Pages\CsbgAnnualReport;
use App\Filament\Pages\FnpiTargets;
use App\Filament\Pages\SrvCodeMapping;
use App\Filament\Resources\CommunityInitiativeResource\Pages\ListCommunityInitiatives;
use App\Filament\Resources\CsbgExpenditureResource\Pages\ListCsbgExpenditures;
use App\Models\User;
use Database\Seeders\CsbgSrvCategorySeeder;
use Database\Seeders\LookupSeeder;
use Database\Seeders\NpiSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->seed(NpiSeeder::class);
    $this->seed(ProgramSeeder::class);

    $this->admin = User::factory()->admin()->create();
    $this->supervisor = User::factory()->supervisor()->create();
    $this->caseworker = User::factory()->caseworker()->create();
});

// =========================================================================
// CSBG Annual Report Page
// =========================================================================

it('admin can access the annual report page', function () {
    $this->actingAs($this->admin);

    Livewire::test(CsbgAnnualReport::class)
        ->assertSuccessful();
});

it('supervisor can access the annual report page', function () {
    $this->actingAs($this->supervisor);

    Livewire::test(CsbgAnnualReport::class)
        ->assertSuccessful();
});

it('caseworker cannot access the annual report page', function () {
    $this->actingAs($this->caseworker);

    $response = $this->get('/admin/csbg-annual-report');
    expect($response->status())->toBeIn([302, 403]);
});

it('admin can generate a report', function () {
    $this->actingAs($this->admin);

    Livewire::test(CsbgAnnualReport::class)
        ->call('generateReport')
        ->assertSet('reportData', fn ($data) => $data !== null);
});

it('admin can regenerate a report', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(CsbgAnnualReport::class)
        ->call('generateReport');

    $firstGenerated = $component->get('generatedAt');

    $component->call('regenerateReport');

    expect($component->get('generatedAt'))->not->toBeNull();
});

// =========================================================================
// FNPI Targets Page
// =========================================================================

it('admin can access the FNPI targets page', function () {
    $this->actingAs($this->admin);

    Livewire::test(FnpiTargets::class)
        ->assertSuccessful();
});

it('FNPI targets page shows all top-level indicators', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(FnpiTargets::class);

    $targets = $component->get('targets');
    expect($targets)->not->toBeEmpty();

    // Should have all top-level indicators (no sub-indicators)
    $codes = collect($targets)->pluck('code')->toArray();
    expect($codes)->toContain('FNPI-1a')
        ->and($codes)->toContain('FNPI-7a')
        ->and($codes)->not->toContain('FNPI-1h.1'); // sub-indicator excluded
});

it('admin can save FNPI targets', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(FnpiTargets::class);

    $targets = $component->get('targets');
    $targets[0]['target'] = 25;

    $component->set('targets', $targets)
        ->call('saveTargets');

    $this->assertDatabaseHas('fnpi_targets', [
        'target_count' => 25,
    ]);
});

// =========================================================================
// Community Initiatives Resource
// =========================================================================

it('admin can access community initiatives list', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListCommunityInitiatives::class)
        ->assertSuccessful();
});

it('supervisor can access community initiatives list', function () {
    $this->actingAs($this->supervisor);

    Livewire::test(ListCommunityInitiatives::class)
        ->assertSuccessful();
});

// =========================================================================
// Expenditures Resource
// =========================================================================

it('admin can access expenditures list', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListCsbgExpenditures::class)
        ->assertSuccessful();
});

// =========================================================================
// SRV Code Mapping Page
// =========================================================================

it('admin can access SRV code mapping page', function () {
    $this->seed(CsbgSrvCategorySeeder::class);

    $this->actingAs($this->admin);

    Livewire::test(SrvCodeMapping::class)
        ->assertSuccessful();
});

it('SRV mapping page loads all categories', function () {
    $this->seed(CsbgSrvCategorySeeder::class);

    $this->actingAs($this->admin);

    $component = Livewire::test(SrvCodeMapping::class);

    $mappings = $component->get('mappings');
    expect(count($mappings))->toBe(144); // All SRV categories
});
