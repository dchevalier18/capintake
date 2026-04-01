<?php

declare(strict_types=1);

use App\Filament\Pages\AuditLogViewer;
use App\Models\AgencySetting;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    AgencySetting::create(['agency_name' => 'Test Agency', 'setup_completed' => true]);
    $this->admin = User::factory()->admin()->create();
    $this->caseworker = User::factory()->caseworker()->create();
});

// --- Auditable Trait ---

it('creates an audit log when a client is created', function () {
    $this->actingAs($this->admin);

    $client = Client::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $log = AuditLog::where('auditable_type', Client::class)
        ->where('auditable_id', $client->id)
        ->where('action', 'created')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->new_values)->toHaveKey('first_name');
    expect($log->new_values['first_name'])->toBe('Jane');
});

it('creates an audit log when a client is updated', function () {
    $this->actingAs($this->admin);

    $client = Client::factory()->create(['first_name' => 'Jane']);

    $client->update(['first_name' => 'Janet']);

    $log = AuditLog::where('auditable_type', Client::class)
        ->where('auditable_id', $client->id)
        ->where('action', 'updated')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->old_values['first_name'])->toBe('Jane');
    expect($log->new_values['first_name'])->toBe('Janet');
});

it('creates an audit log when a client is deleted', function () {
    $this->actingAs($this->admin);

    $client = Client::factory()->create();
    $clientId = $client->id;

    $client->delete();

    $log = AuditLog::where('auditable_type', Client::class)
        ->where('auditable_id', $clientId)
        ->where('action', 'deleted')
        ->first();

    expect($log)->not->toBeNull();
});

it('excludes sensitive fields from audit logs', function () {
    $this->actingAs($this->admin);

    $user = User::factory()->create();

    $logs = AuditLog::where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->get();

    foreach ($logs as $log) {
        if ($log->new_values) {
            expect($log->new_values)->not->toHaveKey('password');
            expect($log->new_values)->not->toHaveKey('remember_token');
        }
        if ($log->old_values) {
            expect($log->old_values)->not->toHaveKey('password');
            expect($log->old_values)->not->toHaveKey('remember_token');
        }
    }
});

it('records the authenticated user in audit log', function () {
    $this->actingAs($this->admin);

    $client = Client::factory()->create();

    $log = AuditLog::where('auditable_type', Client::class)
        ->where('auditable_id', $client->id)
        ->where('action', 'created')
        ->first();

    expect($log->user_id)->toBe($this->admin->id);
});

it('audit log records enrollment changes', function () {
    $this->actingAs($this->admin);

    $enrollment = Enrollment::factory()->create();

    $log = AuditLog::where('auditable_type', Enrollment::class)
        ->where('auditable_id', $enrollment->id)
        ->where('action', 'created')
        ->first();

    expect($log)->not->toBeNull();
});

// --- Audit Log Viewer ---

it('admin can view the audit log page', function () {
    $this->actingAs($this->admin);

    Livewire::test(AuditLogViewer::class)
        ->assertSuccessful();
});

it('caseworker cannot access audit log page', function () {
    $this->actingAs($this->caseworker);

    $this->get('/admin/audit-log-viewer')
        ->assertForbidden();
});
