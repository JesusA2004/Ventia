<?php

use App\Http\Controllers\CompanySelectionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? to_route('dashboard')
        : to_route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('company-selection', [CompanySelectionController::class, 'index'])->name('company-selection.index');
    Route::post('company-selection', [CompanySelectionController::class, 'store'])->name('company-selection.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/catalog.php';
require __DIR__.'/inventory.php';
require __DIR__.'/sales.php';
require __DIR__.'/cash.php';
require __DIR__.'/pos.php';
require __DIR__.'/help.php';
require __DIR__.'/reports.php';
require __DIR__.'/audit.php';
