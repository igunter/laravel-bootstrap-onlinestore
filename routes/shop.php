<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Plain (non-bound) {category}/{brand}/{page} segments here, resolved manually in the
// controllers: letting {category}/{brand} be implicit-bound models would make Laravel pass
// them to the controller positionally in URI order, which breaks the reversed
// brand/category route (see ProductController::index).
foreach ([
    'products' => 'shop.products.index',
    'products/page/{page}' => 'shop.products.index.page',
    'products/category/{category}' => 'shop.products.index.category',
    'products/category/{category}/page/{page}' => 'shop.products.index.category.page',
    'products/brand/{brand}' => 'shop.products.index.brand',
    'products/brand/{brand}/page/{page}' => 'shop.products.index.brand.page',
    'products/category/{category}/brand/{brand}' => 'shop.products.index.category_brand',
    'products/category/{category}/brand/{brand}/page/{page}' => 'shop.products.index.category_brand.page',
    'products/brand/{brand}/category/{category}' => 'shop.products.index.brand_category',
    'products/brand/{brand}/category/{category}/page/{page}' => 'shop.products.index.brand_category.page',
] as $uri => $name) {
    Route::get($uri, [ProductController::class, 'index'])->name($name);
}

Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('shop.products.show');

Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('shop.categories.show');
Route::get('categories/{category:slug}/page/{page}', [CategoryController::class, 'show'])->name('shop.categories.show.page');

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/', [CartController::class, 'store'])->name('store');
    Route::patch('/{rowId}', [CartController::class, 'update'])->name('update');
    Route::delete('/{rowId}', [CartController::class, 'destroy'])->name('destroy');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
});
