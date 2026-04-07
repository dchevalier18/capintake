<?php

use App\Http\Controllers\CsbgExportController;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/admin/health-check', HealthCheckController::class)->name('health-check');

Route::middleware(['auth'])->group(function () {
    Route::get('/csbg/export/csv', [CsbgExportController::class, 'csv'])->name('csbg.export.csv');
    Route::get('/csbg/export/pdf', [CsbgExportController::class, 'pdf'])->name('csbg.export.pdf');
});
