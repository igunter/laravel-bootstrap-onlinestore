<?php

namespace App\Http\Controllers;

use App\Services\SumUp\SumUpCheckoutStatusSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Not actually called by SumUp for this integration — its Checkout API has
 * no separately configured webhook URL, it POSTs the status-changed event to
 * the checkout's own `return_url` (see CheckoutController::returnFromSumUp,
 * which is what SumUp really calls). Kept as a defensive fallback in case a
 * future SumUp account type does support one.
 */
class SumUpWebhookController extends Controller
{
    public function __invoke(Request $request, SumUpCheckoutStatusSync $sync): JsonResponse
    {
        $checkoutId = $request->input('checkout_id') ?? $request->input('id');

        if (! $checkoutId) {
            return response()->json(['message' => 'Missing checkout id.'], 422);
        }

        $sync->syncByCheckoutId($checkoutId);

        return response()->json(['message' => 'ok']);
    }
}
