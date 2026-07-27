<?php

use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('customers-search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('customers/{customer}/sales', [CustomerController::class, 'salesHistory'])->name('customers.sales-history');
    Route::resource('customers', CustomerController::class)->except(['show']);

    Route::get('payment-methods-options', [PaymentMethodController::class, 'options'])->name('payment-methods.options');
    Route::resource('payment-methods', PaymentMethodController::class)
        ->parameters(['payment-methods' => 'payment_method'])
        ->except(['show']);
});
