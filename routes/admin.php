<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\Settings\BranchController;
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\RegisterController;
use App\Http\Controllers\Settings\WarehouseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('settings/company', [CompanyController::class, 'edit'])->name('settings.company.edit');
    Route::patch('settings/company', [CompanyController::class, 'update'])->name('settings.company.update');

    Route::resource('settings/branches', BranchController::class)
        ->parameters(['branches' => 'branch'])
        ->names('settings.branches')
        ->except(['show']);

    Route::resource('settings/warehouses', WarehouseController::class)
        ->parameters(['warehouses' => 'warehouse'])
        ->names('settings.warehouses')
        ->except(['show']);

    Route::resource('settings/registers', RegisterController::class)
        ->parameters(['registers' => 'register'])
        ->names('settings.registers')
        ->except(['show']);

    Route::resource('users', UserController::class)->except(['show']);

    Route::resource('roles', RoleController::class)
        ->only(['index', 'edit', 'update']);
});
