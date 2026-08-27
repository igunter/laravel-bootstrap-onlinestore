<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DemoDataController;
use App\Http\Controllers\Admin\MediaAssetController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductOptionController;
use App\Http\Controllers\Admin\ProductOptionValueController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ProductVariantImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('demo-data/reset', [DemoDataController::class, 'reset'])->name('demo-data.reset');

    Route::post('categories/{category}/move', [CategoryController::class, 'move'])->name('categories.move');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('brands', BrandController::class)->except('show');
    Route::resource('media', MediaAssetController::class)->except('show');
    Route::resource('orders', OrderController::class)->only(['index', 'show', 'update']);

    Route::resource('products', ProductController::class)->except('show');

    Route::prefix('products/{product}')->name('products.')->group(function () {
        Route::delete('images/{media}', [ProductImageController::class, 'destroy'])->name('images.destroy');
        Route::post('images/reorder', [ProductImageController::class, 'reorder'])->name('images.reorder');

        Route::post('options', [ProductOptionController::class, 'store'])->name('options.store');
        Route::put('options/{option}', [ProductOptionController::class, 'update'])->name('options.update');
        Route::delete('options/{option}', [ProductOptionController::class, 'destroy'])->name('options.destroy');

        Route::post('options/{option}/values', [ProductOptionValueController::class, 'store'])->name('options.values.store');
        Route::delete('options/{option}/values/{value}', [ProductOptionValueController::class, 'destroy'])->name('options.values.destroy');

        Route::post('variants/generate', [ProductVariantController::class, 'generate'])->name('variants.generate');
        Route::put('variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
        Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');

        Route::post('variants/{variant}/images', [ProductVariantImageController::class, 'store'])->name('variants.images.store');
        Route::delete('variants/{variant}/images/{media}', [ProductVariantImageController::class, 'destroy'])->name('variants.images.destroy');
        Route::post('variants/{variant}/images/reorder', [ProductVariantImageController::class, 'reorder'])->name('variants.images.reorder');
    });
});
