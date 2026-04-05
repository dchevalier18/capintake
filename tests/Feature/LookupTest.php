<?php

declare(strict_types=1);

use App\Models\LookupCategory;
use App\Models\LookupValue;
use App\Services\Lookup;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(LookupSeeder::class);
});

// =========================================================================
// Lookup::options()
// =========================================================================

it('options returns key => label array for a category', function () {
    $options = Lookup::options('gender');

    expect($options)->toBeArray()
        ->and($options)->toHaveKey('male')
        ->and($options['male'])->toBe('Male')
        ->and($options)->toHaveKey('female')
        ->and($options['female'])->toBe('Female');
});

it('options excludes inactive values', function () {
    $value = LookupValue::whereHas('category', fn ($q) => $q->where('key', 'gender'))
        ->where('key', 'other')
        ->first();

    $value->update(['is_active' => false]);

    $options = Lookup::options('gender');

    expect($options)->not->toHaveKey('other');
});

it('options returns empty array for non-existent category', function () {
    $options = Lookup::options('nonexistent_category');

    expect($options)->toBeArray()->toBeEmpty();
});

// =========================================================================
// Lookup::label()
// =========================================================================

it('label returns display label for a value', function () {
    $label = Lookup::label('race', 'black_african_american');

    expect($label)->toBe('Black or African American');
});

it('label returns null for unknown value', function () {
    $label = Lookup::label('race', 'unknown_value_xyz');

    expect($label)->toBeNull();
});

it('label returns null for null input', function () {
    $label = Lookup::label('race', null);

    expect($label)->toBeNull();
});

// =========================================================================
// Lookup::csbgLabel()
// =========================================================================

it('csbgLabel returns report code when set', function () {
    $label = Lookup::csbgLabel('gender', 'prefer_not_to_say');

    expect($label)->toBe('Unknown/not reported');
});

it('csbgLabel falls back to display label when no report code', function () {
    $label = Lookup::csbgLabel('gender', 'male');

    expect($label)->toBe('Male');
});

// =========================================================================
// Lookup::allValues()
// =========================================================================

it('allValues includes inactive values', function () {
    $value = LookupValue::whereHas('category', fn ($q) => $q->where('key', 'gender'))
        ->where('key', 'other')
        ->first();

    $value->update(['is_active' => false]);

    $all = Lookup::allValues('gender');

    expect($all->pluck('key')->toArray())->toContain('other');
});

// =========================================================================
// System values protection
// =========================================================================

it('system lookup categories cannot be deleted via policy', function () {
    $category = LookupCategory::where('key', 'gender')->first();

    expect($category->is_system)->toBeTrue();

    $admin = \App\Models\User::factory()->admin()->create();
    $policy = new \App\Policies\LookupCategoryPolicy();

    expect($policy->delete($admin, $category))->toBeFalse();
});

it('non-system categories can be deleted', function () {
    $category = LookupCategory::create([
        'key' => 'custom_test',
        'name' => 'Custom Test',
        'is_system' => false,
    ]);

    $admin = \App\Models\User::factory()->admin()->create();
    $policy = new \App\Policies\LookupCategoryPolicy();

    expect($policy->delete($admin, $category))->toBeTrue();
});
