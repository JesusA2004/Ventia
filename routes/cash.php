<?php

use App\Http\Controllers\Cash\CashSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('cash')->name('cash.')->group(function () {
    Route::get('open', [CashSessionController::class, 'create'])->name('sessions.create');
    Route::get('sessions/current', [CashSessionController::class, 'current'])->name('sessions.current');
    Route::get('sessions', [CashSessionController::class, 'index'])->name('sessions.index');
    Route::post('sessions', [CashSessionController::class, 'store'])->name('sessions.store');
    Route::get('sessions/{cash_session}', [CashSessionController::class, 'show'])->name('sessions.show');
    Route::get('sessions/{cash_session}/close', [CashSessionController::class, 'showCloseForm'])->name('sessions.close-screen');
    Route::post('sessions/{cash_session}/close', [CashSessionController::class, 'close'])->name('sessions.close');
    Route::post('sessions/{cash_session}/movements', [CashSessionController::class, 'storeMovement'])->name('sessions.movements.store');
});
