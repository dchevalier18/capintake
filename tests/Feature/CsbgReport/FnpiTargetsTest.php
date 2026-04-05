<?php

declare(strict_types=1);

use App\Models\FnpiTarget;
use App\Models\NpiIndicator;
use Database\Seeders\NpiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(NpiSeeder::class);
});

it('can create FNPI targets for indicators', function () {
    $indicator = NpiIndicator::where('indicator_code', 'FNPI-1a')->first();

    $target = FnpiTarget::create([
        'npi_indicator_id' => $indicator->id,
        'fiscal_year' => 2025,
        'target_count' => 50,
    ]);

    expect($target->exists)->toBeTrue()
        ->and($target->indicator->indicator_code)->toBe('FNPI-1a')
        ->and($target->target_count)->toBe(50);
});

it('enforces unique constraint on indicator + fiscal year', function () {
    $indicator = NpiIndicator::where('indicator_code', 'FNPI-1a')->first();

    FnpiTarget::create([
        'npi_indicator_id' => $indicator->id,
        'fiscal_year' => 2025,
        'target_count' => 50,
    ]);

    expect(fn () => FnpiTarget::create([
        'npi_indicator_id' => $indicator->id,
        'fiscal_year' => 2025,
        'target_count' => 75,
    ]))->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});

it('allows same indicator in different fiscal years', function () {
    $indicator = NpiIndicator::where('indicator_code', 'FNPI-1a')->first();

    FnpiTarget::create([
        'npi_indicator_id' => $indicator->id,
        'fiscal_year' => 2025,
        'target_count' => 50,
    ]);

    $target2 = FnpiTarget::create([
        'npi_indicator_id' => $indicator->id,
        'fiscal_year' => 2026,
        'target_count' => 75,
    ]);

    expect($target2->exists)->toBeTrue();
    expect(FnpiTarget::count())->toBe(2);
});

it('cascades delete when indicator is removed', function () {
    $indicator = NpiIndicator::where('indicator_code', 'FNPI-1a')->first();

    FnpiTarget::create([
        'npi_indicator_id' => $indicator->id,
        'fiscal_year' => 2025,
        'target_count' => 50,
    ]);

    expect(FnpiTarget::count())->toBe(1);

    $indicator->delete();

    expect(FnpiTarget::count())->toBe(0);
});
