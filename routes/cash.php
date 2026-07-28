<?php

use App\Http\Controllers\Cash\CashHandoverController;
use App\Http\Controllers\Cash\CashSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('cash')->name('cash.')->group(function () {
    Route::middleware('active-company')->group(function () {
        Route::get('open', [CashSessionController::class, 'create'])->name('sessions.create');
        Route::post('sessions', [CashSessionController::class, 'store'])->name('sessions.store');
    });
    Route::get('sessions/current', [CashSessionController::class, 'current'])->name('sessions.current');
    Route::get('sessions', [CashSessionController::class, 'index'])->name('sessions.index');
    Route::get('sessions/{cash_session}', [CashSessionController::class, 'show'])->name('sessions.show');
    Route::get('sessions/{cash_session}/close', [CashSessionController::class, 'showCloseForm'])->name('sessions.close-screen');
    Route::post('sessions/{cash_session}/close', [CashSessionController::class, 'close'])->name('sessions.close');
    Route::post('sessions/{cash_session}/movements', [CashSessionController::class, 'storeMovement'])->name('sessions.movements.store');
    Route::post('sessions/{cash_session}/handover', [CashHandoverController::class, 'store'])->name('sessions.handover.store');

    Route::get('handovers', [CashHandoverController::class, 'index'])->name('handovers.index');
    Route::get('handovers/{cash_handover}', [CashHandoverController::class, 'show'])->name('handovers.show');
    Route::post('handovers/{cash_handover}/resolve', [CashHandoverController::class, 'resolve'])->name('handovers.resolve');
});
