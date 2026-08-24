<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/', function () {
        return 'Admin dashboard placeholder';
    })->name('dashboard');
});
