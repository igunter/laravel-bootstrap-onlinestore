<?php

use App\Http\Controllers\SumUpWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// No CSRF middleware here (the api group is stateless by default) — SumUp
// can't obtain a CSRF token, and this endpoint re-verifies everything against
// SumUp's own API rather than trusting the request body anyway.
Route::post('webhooks/sumup', SumUpWebhookController::class)->name('webhooks.sumup');
