<?php

declare(strict_types=1);

use App\Models\FederalPovertyLevel;
use Database\Seeders\FederalPovertyLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds distinct 2025 and 2026 guidelines', function () {
    $this->seed(FederalPovertyLevelSeeder::class);

    expect(FederalPovertyLevel::guidelineFor(1, 2025))->toBe(15650)
        ->and(FederalPovertyLevel::guidelineFor(1, 2026))->toBe(15960)
        ->and(FederalPovertyLevel::guidelineFor(4, 2026))->toBe(33000)
        ->and(FederalPovertyLevel::guidelineFor(1, 2026, 'alaska'))->toBe(19950)
        ->and(FederalPovertyLevel::guidelineFor(1, 2026, 'hawaii'))->toBe(18360);
});

it('falls back to the latest seeded year for an unseeded future year', function () {
    $this->seed(FederalPovertyLevelSeeder::class);

    // 2030 not seeded → falls back to 2026 guidelines instead of null
    expect(FederalPovertyLevel::guidelineFor(1, 2030))->toBe(15960)
        ->and(FederalPovertyLevel::fplPercent(15960, 1, 2030))->toBe(100);
});

it('falls back forward when only later years are seeded', function () {
    FederalPovertyLevel::create([
        'year' => 2026,
        'household_size' => 1,
        'region' => 'continental',
        'poverty_guideline' => 15960,
    ]);

    expect(FederalPovertyLevel::guidelineFor(1, 2024))->toBe(15960);
});

it('returns null when no guidelines exist at all', function () {
    expect(FederalPovertyLevel::guidelineFor(1, 2026))->toBeNull()
        ->and(FederalPovertyLevel::fplPercent(20000, 1))->toBeNull();
});

it('extends guidelines beyond size 8 with the per-person increment', function () {
    $this->seed(FederalPovertyLevelSeeder::class);

    // 2026 continental: size 8 = 55,720 + 2 × 5,680
    expect(FederalPovertyLevel::guidelineFor(10, 2026))->toBe(55720 + 2 * 5680);
});

it('reports whether the current year is seeded', function () {
    expect(FederalPovertyLevel::isCurrentYearSeeded())->toBeFalse();

    $this->seed(FederalPovertyLevelSeeder::class);

    // The seeder covers 2025-2026; this assertion tracks the calendar
    expect(FederalPovertyLevel::isCurrentYearSeeded())->toBe(now()->year <= 2026)
        ->and(FederalPovertyLevel::latestYearAvailable())->toBe(2026);
});
