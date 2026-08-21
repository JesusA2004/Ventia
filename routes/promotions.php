<?php

use App\Http\Controllers\Promotions\CouponController;
use App\Http\Controllers\Promotions\PromotionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('promotions', PromotionController::class)
        ->except(['show'])
        ->names('promotions.promotions');

    Route::resource('coupons', CouponController::class)
        ->except(['show'])
        ->names('promotions.coupons');
});
