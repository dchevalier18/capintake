<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Enums\IntakeStatus;
use App\Filament\Widgets\MyCaseload;
use App\Filament\Widgets\ProgramBreakdown;
use App\Filament\Widgets\QuickActions;
use App\Filament\Widgets\StatsOverview;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// =============================================================================
// StatsOverview
// =============================================================================

it('renders the stats overview widget for an authenticated admin', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(StatsOverview::class)
        ->assertSuccessful();
});

it('shows the correct client count served this month', function () {
    $this->actingAs(User::factory()->admin()->create());

    $program = Program::factory()->create();
    $service = Service::factory()->create(['program_id' => $program->id]);
    $caseworker = User::factory()->caseworker()->create();

    // Create 3 clients with service records dated this month
    $clients = Client::factory()->count(3)->create();
    foreach ($clients as $client) {
        $enrollment = Enrollment::factory()->create([
            'client_id' => $client->id,
            'program_id' => $program->id,
            'caseworker_id' => $caseworker->id,
        ]);
        ServiceRecord::factory()->create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'enrollment_id' => $enrollment->id,
            'provided_by' => $caseworker->id,
            'service_date' => now(),
        ]);
    }

    // Create 1 client with a service record from last year (should not count)
    $oldClient = Client::factory()->create();
    $oldEnrollment = Enrollment::factory()->create([
        'client_id' => $oldClient->id,
        'program_id' => $program->id,
        'caseworker_id' => $caseworker->id,
    ]);
    ServiceRecord::factory()->create([
        'client_id' => $oldClient->id,
        'service_id' => $service->id,
        'enrollment_id' => $oldEnrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => now()->subYear(),
    ]);

    $count = Client::complete()
        ->whereHas('serviceRecords', fn ($q) => $q->where('service_date', '>=', now()->startOfMonth()))
        ->count();

    expect($count)->toBe(3);
});

it('shows the correct intake count this week', function () {
    $this->actingAs(User::factory()->admin()->create());

    // Create 2 clients this week
    Client::factory()->count(2)->create(['created_at' => now()]);

    // Create 1 client last month (should not count)
    Client::factory()->create(['created_at' => now()->subMonth()]);

    $count = Client::complete()
        ->where('created_at', '>=', now()->startOfWeek())
        ->count();

    expect($count)->toBe(2);
});

it('counts unduplicated clients correctly when same client has multiple services', function () {
    $this->actingAs(User::factory()->admin()->create());

    $program = Program::factory()->create();
    $service1 = Service::factory()->create(['program_id' => $program->id]);
    $service2 = Service::factory()->create(['program_id' => $program->id]);
    $caseworker = User::factory()->caseworker()->create();

    // One client with two service records this year
    $client = Client::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'client_id' => $client->id,
        'program_id' => $program->id,
        'caseworker_id' => $caseworker->id,
    ]);
    ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service1->id,
        'enrollment_id' => $enrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => now(),
    ]);
    ServiceRecord::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service2->id,
        'enrollment_id' => $enrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => now()->subDays(5),
    ]);

    $unduplicated = Client::complete()
        ->whereHas('serviceRecords', fn ($q) => $q->where('service_date', '>=', now()->startOfYear()))
        ->distinct()
        ->count('id');

    expect($unduplicated)->toBe(1);
});

// =============================================================================
// MyCaseload
// =============================================================================

it('renders the my caseload widget for an authenticated caseworker', function () {
    $this->actingAs(User::factory()->caseworker()->create());

    Livewire::test(MyCaseload::class)
        ->assertSuccessful();
});

it('shows only the logged-in caseworker enrollments', function () {
    $program = Program::factory()->create();

    $caseworkerA = User::factory()->caseworker()->create();
    $caseworkerB = User::factory()->caseworker()->create();

    // 2 enrollments for caseworker A
    $enrollmentsA = Enrollment::factory()->count(2)->create([
        'program_id' => $program->id,
        'caseworker_id' => $caseworkerA->id,
        'status' => EnrollmentStatus::Active,
    ]);

    // 1 enrollment for caseworker B
    Enrollment::factory()->create([
        'program_id' => $program->id,
        'caseworker_id' => $caseworkerB->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $this->actingAs($caseworkerA);

    Livewire::test(MyCaseload::class)
        ->assertCanSeeTableRecords($enrollmentsA)
        ->assertCountTableRecords(2);
});

it('shows empty state when caseworker has no enrollments', function () {
    $caseworker = User::factory()->caseworker()->create();
    $this->actingAs($caseworker);

    Livewire::test(MyCaseload::class)
        ->assertCountTableRecords(0);
});

// =============================================================================
// QuickActions
// =============================================================================

it('renders the quick actions widget for an authenticated user', function () {
    $this->actingAs(User::factory()->caseworker()->create());

    Livewire::test(QuickActions::class)
        ->assertSuccessful();
});

it('returns matching search results for client search', function () {
    $this->actingAs(User::factory()->caseworker()->create());

    // Create a client with a known name (intake_status defaults to complete)
    Client::factory()->create(['first_name' => 'Johnathan', 'last_name' => 'Testperson']);
    Client::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $widget = Livewire::test(QuickActions::class);
    $widget->set('search', 'Johnathan');

    $results = $widget->instance()->getSearchResults();

    expect($results)->toHaveCount(1);
    expect($results->first()->first_name)->toBe('Johnathan');
});

it('shows the correct draft count', function () {
    $this->actingAs(User::factory()->caseworker()->create());

    // Create 3 draft clients
    Client::factory()->count(3)->create(['intake_status' => IntakeStatus::Draft]);
    // Create 2 complete clients
    Client::factory()->count(2)->create(['intake_status' => IntakeStatus::Complete]);

    $widget = Livewire::test(QuickActions::class);
    $draftCount = $widget->instance()->getDraftCount();

    expect($draftCount)->toBe(3);
});

// =============================================================================
// ProgramBreakdown
// =============================================================================

it('renders the program breakdown widget for an authenticated admin', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ProgramBreakdown::class)
        ->assertSuccessful();
});

it('returns correct chart data for program breakdown', function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->seed(ProgramSeeder::class);

    $caseworker = User::factory()->caseworker()->create();

    // Get the seeded programs and their services
    $csbg = Program::where('code', 'CSBG')->first();
    $emrg = Program::where('code', 'EMRG')->first();

    $csbgService = $csbg->services()->first();
    $emrgService = $emrg->services()->first();

    // Create 3 distinct clients served under CSBG
    for ($i = 0; $i < 3; $i++) {
        $client = Client::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'client_id' => $client->id,
            'program_id' => $csbg->id,
            'caseworker_id' => $caseworker->id,
        ]);
        ServiceRecord::factory()->create([
            'client_id' => $client->id,
            'service_id' => $csbgService->id,
            'enrollment_id' => $enrollment->id,
            'provided_by' => $caseworker->id,
            'service_date' => now(),
        ]);
    }

    // Create 1 client served under Emergency Services
    $emrgClient = Client::factory()->create();
    $emrgEnrollment = Enrollment::factory()->create([
        'client_id' => $emrgClient->id,
        'program_id' => $emrg->id,
        'caseworker_id' => $caseworker->id,
    ]);
    ServiceRecord::factory()->create([
        'client_id' => $emrgClient->id,
        'service_id' => $emrgService->id,
        'enrollment_id' => $emrgEnrollment->id,
        'provided_by' => $caseworker->id,
        'service_date' => now(),
    ]);

    $component = Livewire::test(ProgramBreakdown::class)->instance();

    // Use reflection to call the protected getData() method
    $reflection = new ReflectionMethod($component, 'getData');
    $data = $reflection->invoke($component);

    // Labels should include program codes
    expect($data['labels'])->toContain('CSBG');
    expect($data['labels'])->toContain('EMRG');

    // Find the index positions of CSBG and EMRG
    $csbgIndex = array_search('CSBG', $data['labels']);
    $emrgIndex = array_search('EMRG', $data['labels']);

    // CSBG should have 3 clients served, EMRG should have 1
    expect($data['datasets'][0]['data'][$csbgIndex])->toBe(3);
    expect($data['datasets'][0]['data'][$emrgIndex])->toBe(1);
});
