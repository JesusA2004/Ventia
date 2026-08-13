<?php

use App\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('audit/{audit_log}', [AuditLogController::class, 'show'])->name('audit.show');
});
