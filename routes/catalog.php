<?php

use App\Http\Controllers\Catalog\BrandController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\PriceListController;
use App\Http\Controllers\Catalog\ProductAttributeController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\ProductPriceController;
use App\Http\Controllers\Catalog\TaxController;
use App\Http\Controllers\Catalog\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('categories', CategoryController::class)
        ->except(['show'])
        ->names('catalog.categories');

    Route::resource('brands', BrandController::class)
        ->except(['show'])
        ->names('catalog.brands');

    Route::resource('units', UnitController::class)
        ->except(['show'])
        ->names('catalog.units');

    Route::resource('taxes', TaxController::class)
        ->except(['show'])
        ->names('catalog.taxes');

    Route::resource('price-lists', PriceListController::class)
        ->parameters(['price-lists' => 'priceList'])
        ->except(['show'])
        ->names('catalog.price-lists');

    Route::get('products-search', [ProductController::class, 'search'])->name('products.search');
    Route::post('products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
    Route::post('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::get('products/{product}/price-history', [ProductPriceController::class, 'history'])->name('products.price-history');
    Route::patch('products/{product}/price', [ProductPriceController::class, 'updatePrice'])->name('products.update-price');
    Route::patch('products/{product}/cost', [ProductPriceController::class, 'updateCost'])->name('products.update-cost');
    Route::resource('products', ProductController::class)->except(['show']);

    Route::resource('products-attributes', ProductAttributeController::class)
        ->parameters(['products-attributes' => 'attribute'])
        ->except(['show'])
        ->names('products.attributes');
});
