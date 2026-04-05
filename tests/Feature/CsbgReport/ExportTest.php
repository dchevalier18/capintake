<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use Database\Seeders\LookupSeeder;
use Database\Seeders\NpiSeeder;
use Database\Seeders\NpiServiceMappingSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->seed(NpiSeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(NpiServiceMappingSeeder::class);

    $this->admin = User::factory()->admin()->create();
    $this->caseworker = User::factory()->caseworker()->create();
});

// =========================================================================
// CSV Export
// =========================================================================

it('CSV export returns valid CSV file for authorized user', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('csbg.export.csv', ['year' => 2025]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('CSV export is forbidden for caseworker', function () {
    $this->actingAs($this->caseworker);

    $response = $this->get(route('csbg.export.csv', ['year' => 2025]));

    $response->assertForbidden();
});

it('CSV export contains expected header row', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('csbg.export.csv', ['year' => 2025]));

    $content = $response->streamedContent();

    expect($content)->toContain('NPI Code')
        ->and($content)->toContain('Individuals Served')
        ->and($content)->toContain('GRAND TOTAL');
});

it('CSV export contains data for clients with services', function () {
    // Create a client with a service record in the reporting period
    $client = Client::factory()->create();
    $service = Service::where('code', 'CSBG-VITA')->first();
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

    $this->actingAs($this->admin);

    $response = $this->get(route('csbg.export.csv', ['year' => 2025]));

    $content = $response->streamedContent();

    // CSBG-VITA maps to FNPI-3a
    expect($content)->toContain('FNPI-3a');
});

// =========================================================================
// PDF Export
// =========================================================================

it('PDF export returns valid PDF for authorized user', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('csbg.export.pdf', ['year' => 2025]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

it('PDF export is forbidden for caseworker', function () {
    $this->actingAs($this->caseworker);

    $response = $this->get(route('csbg.export.pdf', ['year' => 2025]));

    $response->assertForbidden();
});
