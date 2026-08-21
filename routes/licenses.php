<?php

use App\Http\Controllers\Settings\LicenseController;
use App\Http\Controllers\SuperAdmin\LicenseKeyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Superadministrator-only serial management ("Licencias Ventia"). The
    // controller itself hard-checks isSuperAdmin() too — see item 28/65.
    Route::prefix('super-admin/licenses')->name('super-admin.licenses.')->group(function () {
        Route::get('/', [LicenseKeyController::class, 'index'])->name('index');
        Route::post('/', [LicenseKeyController::class, 'store'])->name('store');
        Route::post('{licenseKey}/revoke', [LicenseKeyController::class, 'revoke'])->name('revoke');
        Route::post('{licenseKey}/reissue', [LicenseKeyController::class, 'reissue'])->name('reissue');
    });

    // Company-facing "Plan y licencia".
    Route::middleware('active-company')->group(function () {
        Route::get('settings/license', [LicenseController::class, 'show'])->name('settings.license.show');
        Route::post('settings/license/activate', [LicenseController::class, 'activate'])->name('settings.license.activate');
    });
});
