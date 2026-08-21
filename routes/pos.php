<?php

use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\PromotionPreviewController;
use App\Http\Controllers\Pos\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active-company'])->group(function () {
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('pos/products/search', [PosController::class, 'searchProducts'])->name('pos.products.search');
    Route::get('pos/products/barcode', [PosController::class, 'lookupBarcode'])->name('pos.products.barcode');
    Route::post('pos/promotions/preview', PromotionPreviewController::class)->name('pos.promotions.preview');

    Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('sales/suspended', [SaleController::class, 'suspended'])->name('sales.suspended');
    Route::post('sales/suspend', [SaleController::class, 'suspend'])->name('sales.suspend');
    Route::post('sales/{sale}/resume', [SaleController::class, 'resume'])->name('sales.resume');
    Route::delete('sales/{sale}/suspended', [SaleController::class, 'destroySuspended'])->name('sales.destroy-suspended');
    Route::get('sales/{sale}/ticket', [SaleController::class, 'ticket'])->name('sales.ticket');
    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::post('sales/{sale}/returns', [SaleController::class, 'storeReturn'])->name('sales.returns.store');
    Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
});
