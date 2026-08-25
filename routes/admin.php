<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::post('categories/{category}/move', [CategoryController::class, 'move'])->name('categories.move');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('brands', BrandController::class)->except('show');
});
