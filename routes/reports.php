<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('export', [ReportController::class, 'export'])->name('export');
    Route::get('export-pdf', [ReportController::class, 'exportPdf'])->name('export-pdf');
});
