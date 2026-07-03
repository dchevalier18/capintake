<?php

declare(strict_types=1);

use App\Filament\Pages\AgencyCapacity;
use App\Filament\Resources\CnpiResultResource;
use App\Models\AgencyCapacityMetric;
use App\Models\Client;
use App\Models\CnpiIndicator;
use App\Models\CnpiResult;
use App\Models\CsbgReportSetting;
use App\Models\User;
use App\Services\CsbgReportService;
use Database\Seeders\CnpiIndicatorSeeder;
use Database\Seeders\CsbgSrvCategorySeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\FederalPovertyLevelSeeder;
use Database\Seeders\LookupSeeder;
use Database\Seeders\NpiSeeder;
use Database\Seeders\NpiServiceMappingSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->seed(FederalPovertyLevelSeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(NpiSeeder::class);
    $this->seed(NpiServiceMappingSeeder::class);
    $this->seed(CsbgSrvCategorySeeder::class);
    $this->seed(CnpiIndicatorSeeder::class);
});

// =========================================================================
// DemoDataSeeder
// =========================================================================

it('seeds demo data that populates every section c characteristic group', function () {
    $this->seed(DemoDataSeeder::class);

    $settings = CsbgReportSetting::current();
    $fy = $settings->current_fiscal_year;
    [$start, $end] = [($fy - 1).'-10-01', $fy.'-09-30'];

    $report = (new CsbgReportService)->module4SectionC($start, $end);

    expect($report['total_unduplicated_individuals'])->toBeGreaterThan(100)
        ->and($report['total_unduplicated_households'])->toBeGreaterThan(50);

    // Every known-value row in the core demographic groups is non-zero
    foreach (['by_gender', 'by_race', 'by_ethnicity', 'by_household_type', 'by_housing_type'] as $group) {
        $zeroRows = collect($report[$group])->except('unknown')->filter(fn ($c) => $c === 0);
        expect($zeroRows)->toBeEmpty();
    }

    // All ten age bands and all nine FPL bands have counts
    expect(collect($report['by_age'])->except('unknown')->filter(fn ($c) => $c === 0))->toBeEmpty()
        ->and(collect($report['by_fpl_bracket'])->except('unknown')->filter(fn ($c) => $c === 0))->toBeEmpty();

    // Item 13: every composite except no_income (which needs an affirmative
    // declaration the system does not capture) is represented
    $composite = collect($report['by_income_source_composite'])->except('no_income');
    expect($composite->filter(fn ($c) => $c === 0))->toBeEmpty();

    // Modules 2 and 3 have data too
    expect((new CsbgReportService)->module2SectionA($fy))->not->toBeEmpty()
        ->and((new CsbgReportService)->module2SectionB($fy))->not->toBeEmpty()
        ->and(CnpiResult::where('fiscal_year', $fy)->count())->toBeGreaterThan(0);
});

it('refuses to seed demo data when clients already exist', function () {
    Client::factory()->create();

    $this->seed(DemoDataSeeder::class);

    expect(Client::count())->toBe(1);
});

// =========================================================================
// CnpiResultResource (Module 3B UI)
// =========================================================================

it('lists cnpi results for an admin', function () {
    $this->actingAs(User::factory()->admin()->create());
    CnpiResult::factory()->count(2)->create();

    Livewire::test(CnpiResultResource\Pages\ListCnpiResults::class)
        ->assertSuccessful();
});

it('creates a count-of-change cnpi result and auto-computes accuracy', function () {
    $this->actingAs(User::factory()->admin()->create());

    $indicator = CnpiIndicator::forVersion('2.1')
        ->where('cnpi_type', 'count_of_change')
        ->first();

    Livewire::test(CnpiResultResource\Pages\CreateCnpiResult::class)
        ->fillForm([
            'cnpi_indicator_id' => $indicator->id,
            'fiscal_year' => 2026,
            'identified_community' => 'Lehigh Valley',
            'target' => 100,
            'actual_result' => 80,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $result = CnpiResult::where('cnpi_indicator_id', $indicator->id)->first();

    expect($result)->not->toBeNull()
        ->and((float) $result->performance_accuracy)->toBe(80.0);
});

it('lets caseworkers view but not create cnpi results', function () {
    $caseworker = User::factory()->caseworker()->create();

    expect($caseworker->can('viewAny', CnpiResult::class))->toBeTrue()
        ->and($caseworker->can('create', CnpiResult::class))->toBeFalse()
        ->and($caseworker->can('update', CnpiResult::factory()->create()))->toBeFalse();
});

// =========================================================================
// AgencyCapacity page (Module 2B UI)
// =========================================================================

it('saves agency capacity metrics per fiscal year', function () {
    $this->actingAs(User::factory()->admin()->create());

    $component = Livewire::test(AgencyCapacity::class)
        ->set('fiscalYear', 2026);

    $metrics = $component->get('metrics');
    $metrics[0]['value'] = '250';
    $metrics[0]['notes'] = 'Board training hours';
    $component->set('metrics', $metrics)
        ->call('saveMetrics');

    $type = array_key_first(AgencyCapacityMetric::TYPES);

    $this->assertDatabaseHas('agency_capacity_metrics', [
        'fiscal_year' => 2026,
        'metric_type' => $type,
        'metric_value' => 250,
    ]);
});

it('blocks caseworkers from the agency capacity page', function () {
    $this->actingAs(User::factory()->caseworker()->create());

    expect(AgencyCapacity::canAccess())->toBeFalse();
});

// =========================================================================
// Self-sufficiency assessments relation manager
// =========================================================================

it('computes the total score when saving an assessment', function () {
    $client = Client::factory()->create();

    $assessment = $client->selfSufficiencyAssessments()->create([
        'assessed_by' => User::factory()->caseworker()->create()->id,
        'assessment_date' => now(),
        'domain_scores' => ['employment' => 3, 'housing' => 4, 'health' => 5],
    ]);

    expect($assessment->total_score)->toBe(12);
});

// =========================================================================
// License hygiene
// =========================================================================

it('declares the AGPL license consistently', function () {
    expect(file_exists(base_path('LICENSE')))->toBeTrue()
        ->and(file_get_contents(base_path('LICENSE')))->toContain('GNU AFFERO GENERAL PUBLIC LICENSE');

    $composer = json_decode(file_get_contents(base_path('composer.json')), true);
    expect($composer['license'])->toBe('AGPL-3.0-only');
});
