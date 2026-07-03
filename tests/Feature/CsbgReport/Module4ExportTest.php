<?php

declare(strict_types=1);

use App\Enums\IntakeStatus;
use App\Exports\CsbgModule4XlsxExport;
use App\Exports\Module4RowBuilder;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Services\CsbgReportService;
use Database\Seeders\CsbgSrvCategorySeeder;
use Database\Seeders\FederalPovertyLevelSeeder;
use Database\Seeders\LookupSeeder;
use Database\Seeders\NpiSeeder;
use Database\Seeders\NpiServiceMappingSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
    $this->seed(FederalPovertyLevelSeeder::class);
    $this->seed(ProgramSeeder::class);
    $this->seed(NpiSeeder::class);
    $this->seed(NpiServiceMappingSeeder::class);
    $this->seed(CsbgSrvCategorySeeder::class);
});

function serveExportClient(): Client
{
    $client = Client::factory()->create([
        'intake_status' => IntakeStatus::Complete,
        'gender' => 'female',
        'race' => 'white',
    ]);

    $service = Service::where('code', 'CSBG-CM')->first();
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

    return $client;
}

function exportRowBuilder(): Module4RowBuilder
{
    return new Module4RowBuilder(new CsbgReportService, '2025-01-01', '2025-12-31');
}

// =========================================================================
// Row builder content and order
// =========================================================================

it('builds section a rows with the official five-column layout', function () {
    serveExportClient();

    $rows = exportRowBuilder()->sectionARows();

    expect($rows[0])->toContain('I. Individuals Served (#)')
        ->and($rows[0])->toContain('V. Performance Target Accuracy (%)');

    $flat = collect($rows)->pluck(0);
    expect($flat)->toContain('FNPI-1a')
        ->and($flat->first(fn ($v) => str_starts_with((string) $v, 'FNPI 1')))->not->toBeNull();
});

it('builds section c rows in official item order with totals', function () {
    serveExportClient();

    $rows = exportRowBuilder()->sectionCRows();
    $headings = collect($rows)->pluck(0)->map(fn ($v) => (string) $v);

    $order = [
        'A. Total unduplicated number of all INDIVIDUALS about whom one or more characteristics were obtained',
        'B. Total unduplicated number of all HOUSEHOLDS about whom one or more characteristics were obtained',
        '1. Sex',
        '2. Age',
        '4. Disconnected Youth (ages 14-24, neither working nor in school)',
        '6.b. Race',
        '9. Household Type',
        '12. Level of Household Income (% of HHS Guideline)',
        '13. Sources of Household Income',
        '15. Non-Cash Benefits',
        'E. Unduplicated INDIVIDUALS served, by program',
        'F. Unduplicated HOUSEHOLDS served, by program',
    ];

    $positions = collect($order)->map(fn ($h) => $headings->search($h));

    expect($positions->containsStrict(false))->toBeFalse();
    expect($positions->toArray())->toBe($positions->sort()->values()->toArray());
});

it('uses official labels for the income composite and FPL bands', function () {
    serveExportClient();

    $labels = collect(exportRowBuilder()->sectionCRows())->pluck(0)->map(fn ($v) => trim((string) $v));

    expect($labels)->toContain('Income from Employment, Other Income Source, and Non-Cash Benefits')
        ->and($labels)->toContain('251% and over')
        ->and($labels)->toContain('Up to 50%');
});

it('counts the served client in the section c rows', function () {
    serveExportClient();

    $rows = exportRowBuilder()->sectionCRows();

    expect($rows[0][1])->toBe(1)  // A. individuals
        ->and($rows[1][1])->toBe(1); // B. households
});

// =========================================================================
// XLSX writer
// =========================================================================

it('writes a module 4 xlsx workbook', function () {
    serveExportClient();

    $path = (new CsbgModule4XlsxExport(exportRowBuilder()))->write();

    expect(file_exists($path))->toBeTrue()
        ->and(filesize($path))->toBeGreaterThan(1000);

    // XLSX files are zip archives — verify sheet names are present
    $zip = new ZipArchive;
    $zip->open($path);
    $workbook = $zip->getFromName('xl/workbook.xml');
    $zip->close();
    unlink($path);

    expect($workbook)->toContain('FNPI')
        ->and($workbook)->toContain('Services')
        ->and($workbook)->toContain('All Characteristics');
});

// =========================================================================
// HTTP endpoints + authorization
// =========================================================================

it('downloads the xlsx export for an admin', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get('/csbg/export/xlsx?year=2025');

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
});

it('downloads the module 4 csv export for a supervisor', function () {
    $this->actingAs(User::factory()->supervisor()->create());

    $response = $this->get('/csbg/export/module4-csv?year=2025');

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

it('forbids caseworkers from the module 4 exports', function () {
    $this->actingAs(User::factory()->caseworker()->create());

    $this->get('/csbg/export/xlsx?year=2025')->assertForbidden();
    $this->get('/csbg/export/module4-csv?year=2025')->assertForbidden();
});
