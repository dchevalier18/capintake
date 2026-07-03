<?php

declare(strict_types=1);

use App\Enums\IntakeStatus;
use App\Filament\Pages\ClientImport;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientImportService;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
});

function importCsv(string $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'import-test-').'.csv';
    file_put_contents($path, $content);

    return $path;
}

it('imports clients from a csv with auto-guessed mapping', function () {
    $path = importCsv(<<<'CSV'
First Name,Last Name,DOB,Gender,Race,Street Address,City,State,Zip
Maria,Gonzalez,1985-04-12,Female,White,12 Oak St,Allentown,PA,18101
James,Carter,1970-11-02,Male,Black/African American,88 Pine Ave,Bethlehem,PA,18015
CSV);

    $importer = new ClientImportService;
    [$headers, $rows] = $importer->parse($path);
    $result = $importer->import($rows, $importer->guessMapping($headers));

    expect($result['created'])->toBe(2)
        ->and($result['errors'])->toBeEmpty();

    $maria = Client::where('first_name', 'Maria')->first();
    expect($maria->gender)->toBe('female')
        ->and($maria->race)->toBe('white')
        ->and($maria->intake_status)->toBe(IntakeStatus::Complete)
        ->and($maria->household->city)->toBe('Allentown');

    // Synonym normalization: "Black/African American" -> lookup key
    expect(Client::where('first_name', 'James')->first()->race)->toBe('black_african_american');

    unlink($path);
});

it('collects bad rows as errors without aborting the import', function () {
    $path = importCsv(<<<'CSV'
First Name,Last Name,DOB
Good,Row,1990-01-01
,MissingFirst,1990-01-01
Bad,Date,not-a-date
CSV);

    $importer = new ClientImportService;
    [$headers, $rows] = $importer->parse($path);
    $result = $importer->import($rows, $importer->guessMapping($headers));

    // Row with a bad date still imports (with DOB blanked + warning)
    expect($result['created'])->toBe(2)
        ->and($result['errors'])->toHaveCount(2)
        ->and($result['errors'][3])->toContain('Missing first or last name')
        ->and(Client::where('first_name', 'Bad')->first()->date_of_birth)->toBeNull();

    unlink($path);
});

it('skips duplicates by name and by ssn last four', function () {
    Client::factory()->create([
        'first_name' => 'Existing',
        'last_name' => 'Person',
        'ssn_last_four' => '4321',
        'intake_status' => IntakeStatus::Complete,
    ]);

    $path = importCsv(<<<'CSV'
First Name,Last Name,SSN
Existing,Person,
Different,Name,987654321
Brand,New,111223333
CSV);

    $importer = new ClientImportService;
    [$headers, $rows] = $importer->parse($path);
    $result = $importer->import($rows, $importer->guessMapping($headers));

    // "Existing Person" duplicates by name; "Different Name" duplicates by
    // SSN last four 4321; "Brand New" imports
    expect($result['skipped_duplicates'])->toBe(2)
        ->and($result['created'])->toBe(1);

    unlink($path);
});

it('does not write anything on a dry run', function () {
    $path = importCsv(<<<'CSV'
First Name,Last Name
Dry,Run
CSV);

    $importer = new ClientImportService;
    [$headers, $rows] = $importer->parse($path);
    $result = $importer->import($rows, $importer->guessMapping($headers), dryRun: true);

    expect($result['created'])->toBe(1)
        ->and(Client::count())->toBe(0);

    unlink($path);
});

it('requires name columns in the mapping', function () {
    $importer = new ClientImportService;

    expect(fn () => $importer->import([['x']], [0 => 'phone']))
        ->toThrow(InvalidArgumentException::class);
});

it('imports via the artisan command', function () {
    $path = importCsv(<<<'CSV'
First Name,Last Name
Console,Import
CSV);

    $this->artisan('capintake:import-clients', ['file' => $path])
        ->assertSuccessful();

    expect(Client::where('first_name', 'Console')->exists())->toBeTrue();

    unlink($path);
});

it('restricts the import page to admins', function () {
    $admin = User::factory()->admin()->create();
    $caseworker = User::factory()->caseworker()->create();

    $this->actingAs($admin);
    expect(ClientImport::canAccess())->toBeTrue();
    Livewire::test(ClientImport::class)->assertSuccessful();

    $this->actingAs($caseworker);
    expect(ClientImport::canAccess())->toBeFalse();
});
