<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\CsbgSrvCategory;
use App\Models\Enrollment;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Services\CsbgReportService;
use Database\Seeders\CsbgSrvCategorySeeder;
use Database\Seeders\LookupSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(CsbgSrvCategorySeeder::class);
});

function linkServiceToSrv(string $serviceCode, string $srvCode): void
{
    $service = Service::where('code', $serviceCode)->firstOrFail();
    $srv = CsbgSrvCategory::where('code', $srvCode)->firstOrFail();
    $service->srvCategories()->syncWithoutDetaching([$srv->id]);
}

function createSrvServiceRecord(string $serviceCode, Client $client, string $date): ServiceRecord
{
    $service = Service::where('code', $serviceCode)->firstOrFail();
    $caseworker = User::factory()->caseworker()->create();
    $enrollment = Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $service->program_id,
        'caseworker_id' => $caseworker->id,
    ]);

    return ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'enrollment_id' => $enrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => $date,
    ]);
}

it('returns all SRV categories grouped by domain', function () {
    $report = (new CsbgReportService())->module4SectionB('2025-01-01', '2025-12-31');

    expect($report)->toHaveCount(7); // 7 domains

    $domains = $report->pluck('domain')->toArray();
    expect($domains)->toContain('employment')
        ->and($domains)->toContain('education')
        ->and($domains)->toContain('health_social')
        ->and($domains)->toContain('multi_domain');
});

it('shows zero for unmapped SRV categories', function () {
    $report = (new CsbgReportService())->module4SectionB('2025-01-01', '2025-12-31');

    // Without any mappings, all categories should show 0
    $employment = $report->firstWhere('domain', 'employment');
    expect($employment['domain_total'])->toBe(0);

    $firstCategory = $employment['categories'][0];
    expect($firstCategory['unduplicated_clients'])->toBe(0)
        ->and($firstCategory['total_services'])->toBe(0);
});

it('counts unduplicated clients for mapped SRV categories', function () {
    // Link CSBG-ERT to SRV 1f (Job Readiness Training)
    linkServiceToSrv('CSBG-ERT', 'SRV 1f');

    $client = Client::factory()->create();
    createSrvServiceRecord('CSBG-ERT', $client, '2025-06-01');
    createSrvServiceRecord('CSBG-ERT', $client, '2025-06-15'); // same client, 2 services

    $report = (new CsbgReportService())->module4SectionB('2025-01-01', '2025-12-31');

    $employment = $report->firstWhere('domain', 'employment');
    $srv1f = collect($employment['categories'])->firstWhere('code', 'SRV 1f');

    expect($srv1f['unduplicated_clients'])->toBe(1)
        ->and($srv1f['total_services'])->toBe(2);
});

it('excludes services outside the date range', function () {
    linkServiceToSrv('CSBG-ERT', 'SRV 1f');

    $client = Client::factory()->create();
    createSrvServiceRecord('CSBG-ERT', $client, '2025-06-01');
    createSrvServiceRecord('CSBG-ERT', $client, '2024-01-01'); // outside period

    $report = (new CsbgReportService())->module4SectionB('2025-01-01', '2025-12-31');

    $employment = $report->firstWhere('domain', 'employment');
    $srv1f = collect($employment['categories'])->firstWhere('code', 'SRV 1f');

    expect($srv1f['total_services'])->toBe(1);
});
