<?php

use App\Http\Controllers\Inventory\InventoryBalanceController;
use App\Http\Controllers\Inventory\KardexController;
use App\Http\Controllers\Inventory\ProductLotController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockCountController;
use App\Http\Controllers\Inventory\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('inventory/balances', [InventoryBalanceController::class, 'index'])->name('inventory.balances.index');

    Route::get('inventory/kardex', [KardexController::class, 'index'])->name('inventory.kardex.index');
    Route::get('inventory/kardex/export', [KardexController::class, 'export'])->name('inventory.kardex.export');

    Route::get('inventory/adjustments/create', [StockAdjustmentController::class, 'create'])->name('inventory.adjustments.create');
    Route::post('inventory/adjustments', [StockAdjustmentController::class, 'store'])->name('inventory.adjustments.store');

    Route::post('inventory/transfers/{transfer}/submit', [StockTransferController::class, 'submit'])->name('inventory.transfers.submit');
    Route::post('inventory/transfers/{transfer}/approve', [StockTransferController::class, 'approve'])->name('inventory.transfers.approve');
    Route::post('inventory/transfers/{transfer}/ship', [StockTransferController::class, 'ship'])->name('inventory.transfers.ship');
    Route::post('inventory/transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('inventory.transfers.receive');
    Route::post('inventory/transfers/{transfer}/cancel', [StockTransferController::class, 'cancel'])->name('inventory.transfers.cancel');
    Route::resource('inventory/transfers', StockTransferController::class)
        ->parameters(['transfers' => 'transfer'])
        ->except(['edit', 'update', 'destroy'])
        ->names('inventory.transfers');

    Route::post('inventory/counts/{count}/complete', [StockCountController::class, 'complete'])->name('inventory.counts.complete');
    Route::post('inventory/counts/{count}/apply', [StockCountController::class, 'apply'])->name('inventory.counts.apply');
    Route::post('inventory/counts/{count}/cancel', [StockCountController::class, 'cancel'])->name('inventory.counts.cancel');
    Route::resource('inventory/counts', StockCountController::class)
        ->parameters(['counts' => 'count'])
        ->except(['edit', 'update', 'destroy'])
        ->names('inventory.counts');

    Route::resource('inventory/lots', ProductLotController::class)
        ->parameters(['lots' => 'lot'])
        ->except(['show'])
        ->names('inventory.lots');
});
