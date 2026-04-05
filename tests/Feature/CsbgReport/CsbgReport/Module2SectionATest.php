<?php

declare(strict_types=1);

use App\Models\CsbgExpenditure;
use App\Services\CsbgReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns expenditures for a fiscal year', function () {
    CsbgExpenditure::create([
        'fiscal_year' => 2025,
        'reporting_period' => 'oct_sep',
        'domain' => 'employment',
        'csbg_funds' => 50000.00,
    ]);

    CsbgExpenditure::create([
        'fiscal_year' => 2025,
        'reporting_period' => 'oct_sep',
        'domain' => 'housing',
        'csbg_funds' => 75000.00,
    ]);

    $report = (new CsbgReportService())->module2SectionA(2025);

    expect($report)->toHaveCount(2)
        ->and($report->firstWhere('domain', 'employment')['csbg_funds'])->toBe(50000.00)
        ->and($report->firstWhere('domain', 'housing')['csbg_funds'])->toBe(75000.00);
});

it('excludes expenditures from other fiscal years', function () {
    CsbgExpenditure::create([
        'fiscal_year' => 2025,
        'reporting_period' => 'oct_sep',
        'domain' => 'employment',
        'csbg_funds' => 50000.00,
    ]);

    CsbgExpenditure::create([
        'fiscal_year' => 2024,
        'reporting_period' => 'oct_sep',
        'domain' => 'housing',
        'csbg_funds' => 30000.00,
    ]);

    $report = (new CsbgReportService())->module2SectionA(2025);

    expect($report)->toHaveCount(1);
});

it('returns empty collection when no expenditures exist', function () {
    $report = (new CsbgReportService())->module2SectionA(2025);

    expect($report)->toHaveCount(0);
});
