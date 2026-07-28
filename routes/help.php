<?php

use App\Http\Controllers\HelpController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('help')->name('help.')->group(function () {
    Route::get('getting-started', [HelpController::class, 'gettingStarted'])->name('getting-started');
    Route::get('guide', [HelpController::class, 'guide'])->name('guide');
});
