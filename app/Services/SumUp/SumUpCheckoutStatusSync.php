<?php

namespace App\Services\SumUp;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Notifications\OrderPaid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Re-verifies a checkout's status directly against SumUp (never trusting a
 * webhook body) and applies it to the matching order. Shared by the two
 * places SumUp's status notification can arrive:
 *  - CheckoutController::returnFromSumUp — SumUp's Checkout API has no
 *    separate webhook registration; it POSTs the status-changed event to the
 *    same `return_url` given at checkout creation, so that route has to
 *    double as the real webhook receiver.
 *  - SumUpWebhookController — kept as a defensive fallback in case a future
 *    SumUp account type does support a separately configured webhook URL.
 */
class SumUpCheckoutStatusSync
{
    public function __construct(private SumUpClient $sumUp) {}

    public function syncByCheckoutId(string $checkoutId): void
    {
        $order = Order::where('sumup_checkout_id', $checkoutId)->first();

        if ($order) {
            $this->sync($order);
        }
    }

    public function sync(Order $order): void
    {
        if (! $order->sumup_checkout_id) {
            return;
        }

        $checkout = $this->sumUp->getCheckout($order->sumup_checkout_id);
        $status = strtoupper((string) ($checkout['status'] ?? ''));

        if ($status === 'PAID') {
            $this->markPaid($order);
        } elseif ($status === 'FAILED' && $order->status !== OrderStatus::Paid) {
            $order->update(['status' => OrderStatus::Failed]);
        }
    }

    private function markPaid(Order $order): void
    {
        // Guards against a retried/duplicate notification decrementing stock
        // twice for the same order.
        if ($order->status === OrderStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

            foreach ($order->items as $item) {
                if (! $item->product_variant_id) {
                    continue;
                }

                ProductVariant::whereKey($item->product_variant_id)->decrement('stock_quantity', $item->quantity);
            }
        });

        // Routed by email rather than $order->user, since a guest order has no
        // account to notify through.
        Notification::route('mail', $order->contact_email)->notify(new OrderPaid($order));
    }
}
