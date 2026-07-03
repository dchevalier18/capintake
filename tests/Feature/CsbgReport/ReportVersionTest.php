<?php

declare(strict_types=1);

use App\Enums\IntakeStatus;
use App\Filament\Pages\FnpiTargets;
use App\Models\Client;
use App\Models\CsbgReportSetting;
use App\Models\CsbgSrvCategory;
use App\Models\Enrollment;
use App\Models\FnpiTarget;
use App\Models\NpiGoal;
use App\Models\NpiIndicator;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Services\CsbgReportService;
use Database\Seeders\CsbgSrvCategorySeeder;
use Database\Seeders\LookupSeeder;
use Database\Seeders\NpiSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(NpiSeeder::class);
    $this->seed(CsbgSrvCategorySeeder::class);
});

it('seeds the 2.1 taxonomy with a report version', function () {
    expect(NpiIndicator::forVersion('2.1')->count())->toBeGreaterThan(20)
        ->and(NpiIndicator::forVersion('3.0')->count())->toBe(0)
        ->and(CsbgReportSetting::current()->refresh()->report_version)->toBe('2.1');
});

it('re-seeding preserves indicator ids and their fnpi targets', function () {
    $indicator = NpiIndicator::forVersion('2.1')->where('indicator_code', 'FNPI-1a')->first();

    FnpiTarget::create([
        'npi_indicator_id' => $indicator->id,
        'fiscal_year' => 2026,
        'target_count' => 50,
    ]);

    $this->seed(NpiSeeder::class);

    $reloaded = NpiIndicator::forVersion('2.1')->where('indicator_code', 'FNPI-1a')->first();

    expect($reloaded->id)->toBe($indicator->id)
        ->and(FnpiTarget::where('npi_indicator_id', $indicator->id)->where('target_count', 50)->exists())->toBeTrue();
});

it('scopes taxonomy queries to the configured report version', function () {
    $goal = NpiGoal::where('goal_number', 1)->first();

    // A fake 3.0 indicator sharing a 2.1 code
    NpiIndicator::create([
        'npi_goal_id' => $goal->id,
        'indicator_code' => 'FNPI-1a',
        'name' => 'v3 revised indicator',
        'report_version' => '3.0',
    ]);

    expect(NpiIndicator::forVersion('2.1')->where('indicator_code', 'FNPI-1a')->count())->toBe(1)
        ->and(NpiIndicator::forVersion('3.0')->where('indicator_code', 'FNPI-1a')->count())->toBe(1);

    CsbgReportSetting::current()->update(['report_version' => '3.0']);

    expect(NpiIndicator::forVersion()->where('indicator_code', 'FNPI-1a')->first()->name)
        ->toBe('v3 revised indicator');
});

it('module 4B only aggregates SRV categories of the active version', function () {
    // Serve one client through a 2.1-mapped service
    $service = Service::where('code', 'CSBG-CM')->first();
    $srv = CsbgSrvCategory::forVersion('2.1')->where('code', 'SRV 7a')->first()
        ?? CsbgSrvCategory::forVersion('2.1')->first();
    $srv->services()->syncWithoutDetaching([$service->id]);

    $client = Client::factory()->create(['intake_status' => IntakeStatus::Complete]);
    $caseworker = User::factory()->caseworker()->create();
    $enrollment = Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $service->program_id,
        'caseworker_id' => $caseworker->id,
    ]);
    ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'enrollment_id' => $enrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => '2025-06-01',
    ]);

    $v21 = (new CsbgReportService)->forVersion('2.1')->module4SectionB('2025-01-01', '2025-12-31');
    $v30 = (new CsbgReportService)->forVersion('3.0')->module4SectionB('2025-01-01', '2025-12-31');

    $v21Total = collect($v21)->sum(fn ($domain) => collect($domain['categories'])->sum('unduplicated_clients'));

    expect($v21Total)->toBeGreaterThan(0)
        ->and($v30)->toBeEmpty();
});

it('module 4A respects the version filter', function () {
    $report21 = (new CsbgReportService)->forVersion('2.1')->module4SectionA('2025-01-01', '2025-12-31');
    $report30 = (new CsbgReportService)->forVersion('3.0')->module4SectionA('2025-01-01', '2025-12-31');

    expect($report21->count())->toBeGreaterThan(0)
        ->and($report30->count())->toBe(0);
});

it('shows only active-version indicators on the FNPI targets page', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $goal = NpiGoal::where('goal_number', 1)->first();
    NpiIndicator::create([
        'npi_goal_id' => $goal->id,
        'indicator_code' => 'FNPI-9z',
        'name' => 'Future 3.0 indicator',
        'report_version' => '3.0',
    ]);

    $component = Livewire\Livewire::test(FnpiTargets::class);

    $codes = collect($component->get('targets'))->pluck('code');

    expect($codes)->toContain('FNPI-1a')
        ->and($codes)->not->toContain('FNPI-9z');
});
