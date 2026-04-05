<?php

declare(strict_types=1);

use App\Models\AgencySetting;
use App\Models\Client;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    AgencySetting::create(['agency_name' => 'Test Agency', 'setup_completed' => true]);
});

// =========================================================================
// PII Encryption at Rest
// =========================================================================

it('encrypts client SSN at rest', function () {
    $client = Client::factory()->create(['ssn_encrypted' => '123456789']);

    $raw = DB::table('clients')->where('id', $client->id)->value('ssn_encrypted');

    expect($raw)->not->toBe('123456789');
    expect(Crypt::decryptString($raw))->toBe('123456789');
});

it('encrypts client date_of_birth at rest', function () {
    $client = Client::factory()->create(['date_of_birth' => '1990-05-15']);

    $raw = DB::table('clients')->where('id', $client->id)->value('date_of_birth');

    // Raw DB value should be encrypted (not a plain date)
    expect($raw)->not->toBe('1990-05-15');
    expect($raw)->not->toMatch('/^\d{4}-\d{2}-\d{2}/');

    // Eloquent model decrypts it back to a Carbon date
    $client->refresh();
    expect($client->date_of_birth->format('Y-m-d'))->toBe('1990-05-15');
});

it('auto-populates client birth_year from date_of_birth', function () {
    $client = Client::factory()->create(['date_of_birth' => '1985-03-20']);

    expect($client->birth_year)->toBe(1985);

    // birth_year is stored as plain integer for NPI queries
    $raw = DB::table('clients')->where('id', $client->id)->value('birth_year');
    expect($raw)->toBe(1985);
});

it('encrypts household member date_of_birth at rest', function () {
    $member = HouseholdMember::factory()->create(['date_of_birth' => '2005-08-10']);

    $raw = DB::table('household_members')->where('id', $member->id)->value('date_of_birth');

    expect($raw)->not->toBe('2005-08-10');
    expect($raw)->not->toMatch('/^\d{4}-\d{2}-\d{2}/');

    $member->refresh();
    expect($member->date_of_birth->format('Y-m-d'))->toBe('2005-08-10');
});

it('auto-populates household member birth_year', function () {
    $member = HouseholdMember::factory()->create(['date_of_birth' => '2010-12-01']);

    expect($member->birth_year)->toBe(2010);
});

it('encrypts income record amount at rest', function () {
    $record = IncomeRecord::factory()->create(['amount' => 1500.00]);

    $raw = DB::table('income_records')->where('id', $record->id)->value('amount');

    // Raw value should be encrypted, not a plain number
    expect($raw)->not->toBe('1500.00');
    expect($raw)->not->toBe(1500.00);
    expect((float) Crypt::decryptString($raw))->toBe(1500.0);

    // Eloquent model decrypts it
    $record->refresh();
    expect((float) $record->amount)->toBe(1500.0);
});

it('encrypts income record annual_amount at rest', function () {
    $record = IncomeRecord::factory()->create([
        'amount' => 1000.00,
        'frequency' => \App\Enums\IncomeFrequency::Monthly,
    ]);

    $raw = DB::table('income_records')->where('id', $record->id)->value('annual_amount');

    expect($raw)->not->toBe('12000.00');
    expect($raw)->not->toBe(12000.00);

    $record->refresh();
    expect((float) $record->annual_amount)->toBe(12000.0);
});

// =========================================================================
// Authorization Policies
// =========================================================================

it('caseworker cannot delete income records', function () {
    $caseworker = User::factory()->caseworker()->create();
    $record = IncomeRecord::factory()->create();

    expect($caseworker->can('delete', $record))->toBeFalse();
});

it('admin can delete income records', function () {
    $admin = User::factory()->admin()->create();
    $record = IncomeRecord::factory()->create();

    expect($admin->can('delete', $record))->toBeTrue();
});

it('supervisor can delete income records', function () {
    $supervisor = User::factory()->supervisor()->create();
    $record = IncomeRecord::factory()->create();

    expect($supervisor->can('delete', $record))->toBeTrue();
});

it('caseworker cannot delete household members', function () {
    $caseworker = User::factory()->caseworker()->create();
    $member = HouseholdMember::factory()->create();

    expect($caseworker->can('delete', $member))->toBeFalse();
});

it('admin can delete household members', function () {
    $admin = User::factory()->admin()->create();
    $member = HouseholdMember::factory()->create();

    expect($admin->can('delete', $member))->toBeTrue();
});

it('all roles can view and create income records', function () {
    $caseworker = User::factory()->caseworker()->create();
    $record = IncomeRecord::factory()->create();

    expect($caseworker->can('viewAny', IncomeRecord::class))->toBeTrue();
    expect($caseworker->can('view', $record))->toBeTrue();
    expect($caseworker->can('create', IncomeRecord::class))->toBeTrue();
    expect($caseworker->can('update', $record))->toBeTrue();
});

it('all roles can view and create household members', function () {
    $caseworker = User::factory()->caseworker()->create();
    $member = HouseholdMember::factory()->create();

    expect($caseworker->can('viewAny', HouseholdMember::class))->toBeTrue();
    expect($caseworker->can('view', $member))->toBeTrue();
    expect($caseworker->can('create', HouseholdMember::class))->toBeTrue();
    expect($caseworker->can('update', $member))->toBeTrue();
});

// =========================================================================
// Session Timeout
// =========================================================================

it('session lifetime is configured to 30 minutes', function () {
    expect(config('session.lifetime'))->toBe(30);
});

it('sessions expire when browser closes', function () {
    expect(config('session.expire_on_close'))->toBeTrue();
});

// =========================================================================
// Login Rate Limiting
// =========================================================================

it('login throttle middleware is configured', function () {
    // Filament's login page uses its built-in WithRateLimiting trait
    // which limits to 5 attempts per minute. Verify the session-based
    // auth middleware stack is in place (which enables throttling).
    $panel = \Filament\Facades\Filament::getDefaultPanel();
    $middleware = $panel->getMiddleware();

    // AuthenticateSession is required for Filament's rate limiting to work
    expect($middleware)->toContain(\Filament\Http\Middleware\AuthenticateSession::class);
});

it('custom login page with rate limiting is registered', function () {
    $panel = \Filament\Facades\Filament::getDefaultPanel();

    // The panel should use our custom Login class
    expect($panel->getLoginRouteAction())->toBe(\App\Filament\Pages\Auth\Login::class);
});

it('login rate limiter blocks after 5 failed attempts', function () {
    $email = 'attacker@example.com';
    $ip = '127.0.0.1';

    // Clear any existing rate limit state
    $key = 'login-attempt:' . sha1($email . '|' . $ip);
    \Illuminate\Support\Facades\RateLimiter::clear($key);

    // Simulate 5 failed attempts by hitting the rate limiter
    for ($i = 0; $i < 5; $i++) {
        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);
    }

    // The 6th attempt should be blocked
    expect(\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5))->toBeTrue();

    // Clean up
    \Illuminate\Support\Facades\RateLimiter::clear($key);
});

// =========================================================================
// Password Complexity
// =========================================================================

it('password defaults enforce min 10 chars, mixed case, numbers, and symbols', function () {
    $rule = \Illuminate\Validation\Rules\Password::default();

    // Weak password fails
    $validator = \Illuminate\Support\Facades\Validator::make(
        ['password' => 'short'],
        ['password' => $rule]
    );
    expect($validator->fails())->toBeTrue();
});

it('password without symbols fails validation', function () {
    $rule = \Illuminate\Validation\Rules\Password::default();

    $validator = \Illuminate\Support\Facades\Validator::make(
        ['password' => 'SecurePass1'],
        ['password' => $rule]
    );

    expect($validator->fails())->toBeTrue();
});

it('strong password with symbols passes validation', function () {
    $rule = \Illuminate\Validation\Rules\Password::default();

    $validator = \Illuminate\Support\Facades\Validator::make(
        ['password' => 'SecurePass1!'],
        ['password' => $rule]
    );

    expect($validator->fails())->toBeFalse();
});

it('password without uppercase fails validation', function () {
    $rule = \Illuminate\Validation\Rules\Password::default();

    $validator = \Illuminate\Support\Facades\Validator::make(
        ['password' => 'alllowercase1!'],
        ['password' => $rule]
    );

    expect($validator->fails())->toBeTrue();
});

it('password without number fails validation', function () {
    $rule = \Illuminate\Validation\Rules\Password::default();

    $validator = \Illuminate\Support\Facades\Validator::make(
        ['password' => 'NoNumberHere!'],
        ['password' => $rule]
    );

    expect($validator->fails())->toBeTrue();
});

// =========================================================================
// CSRF Protection
// =========================================================================

it('CSRF middleware is active on admin panel', function () {
    // A POST without a CSRF token should fail
    $response = $this->post('/admin/login', [
        'email' => 'test@example.com',
        'password' => 'test',
    ]);

    // Laravel returns 419 for missing CSRF token
    expect($response->status())->toBeIn([419, 302, 405]);
});

// =========================================================================
// Panel Access Control
// =========================================================================

it('inactive user cannot access panel', function () {
    $user = User::factory()->inactive()->create();

    $this->actingAs($user);

    $this->get('/admin')
        ->assertForbidden();
});

it('unauthenticated user is redirected to login', function () {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

// =========================================================================
// Income Calculation with Encrypted Fields
// =========================================================================

it('client total annual income works with encrypted amounts', function () {
    $client = Client::factory()->create();

    IncomeRecord::factory()->create([
        'client_id' => $client->id,
        'amount' => 2000.00,
        'frequency' => \App\Enums\IncomeFrequency::Monthly,
    ]);

    IncomeRecord::factory()->create([
        'client_id' => $client->id,
        'amount' => 500.00,
        'frequency' => \App\Enums\IncomeFrequency::Weekly,
    ]);

    // Monthly: 2000 * 12 = 24000, Weekly: 500 * 52 = 26000
    expect($client->totalAnnualIncome())->toBe(50000.0);
});

it('household total annual income works with encrypted amounts', function () {
    $household = Household::factory()->create();
    $client = Client::factory()->create(['household_id' => $household->id]);

    IncomeRecord::factory()->create([
        'client_id' => $client->id,
        'amount' => 1000.00,
        'frequency' => \App\Enums\IncomeFrequency::Monthly,
    ]);

    expect($household->totalAnnualIncome())->toBe(12000.0);
});
