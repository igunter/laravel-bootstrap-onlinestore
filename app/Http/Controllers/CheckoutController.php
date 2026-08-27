<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\SumUp\SumUpClient;
use App\Services\SumUp\SumUpCheckoutStatusSync;
use App\Support\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Cart $cart): View|RedirectResponse
    {
        if ($cart->items()->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        return view('shop.checkout.show', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
        ]);
    }

    public function store(Request $request, Cart $cart, SumUpClient $sumUp): RedirectResponse
    {
        $items = $cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'shipping_address_line1' => ['required', 'string', 'max:255'],
            'shipping_address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:255'],
            'shipping_postcode' => ['required', 'string', 'max:255'],
            'shipping_country' => ['required', 'string', 'max:255'],
        ]);

        // Re-validate stock against what's live right now — the cart's own
        // snapshot could be stale if it's been sitting open a while.
        foreach ($items as $item) {
            $variant = ProductVariant::find($item['variant_id']);

            if (! $variant || ! $variant->is_active || $variant->stock_quantity < $item['quantity']) {
                return redirect()->route('cart.index')
                    ->with('error', "Sorry, \"{$item['name']}\" no longer has enough stock — please update your cart.");
            }
        }

        try {
            $order = DB::transaction(function () use ($data, $items, $sumUp) {
                // Built from the cart's own snapshot, not re-queried live prices —
                // what the shopper saw at checkout is what they're charged.
                $subtotal = $items->sum(fn (array $item) => $item['unit_price'] * $item['quantity']);

                $order = Order::create([
                    ...$data,
                    'user_id' => Auth::id(),
                    'order_number' => Order::generateOrderNumber(),
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                    // The migration defaults this at the DB level, but Eloquent
                    // has no idea that happened until the model is refreshed —
                    // $order->currency below would be null without this, since
                    // it's read from the in-memory model straight after create().
                    'currency' => 'GBP',
                ]);

                foreach ($items as $item) {
                    $order->items()->create([
                        'product_variant_id' => $item['variant_id'],
                        'product_name' => $item['name'],
                        'variant_sku' => $item['sku'],
                        'variant_options_label' => $item['options_label'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'line_total' => $item['unit_price'] * $item['quantity'],
                    ]);
                }

                // If SumUp can't be reached or rejects the request, this throws
                // and the whole transaction (order + items) rolls back — no
                // orphaned pending order left behind, and the cart is untouched
                // below so the shopper can just retry.
                $checkout = $sumUp->createCheckout([
                    'checkout_reference' => $order->order_number,
                    'amount' => (float) $order->total,
                    'currency' => $order->currency,
                    'merchant_code' => config('services.sumup.merchant_code'),
                    'description' => "Order {$order->order_number}",
                    'return_url' => route('checkout.return', $order),
                ]);

                $order->update(['sumup_checkout_id' => $checkout['id']]);

                return $order;
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('checkout.show')
                ->with('error', 'We could not start payment with our card processor — please try again.');
        }

        $cart->clear();

        // A guest has no account to look this order up against later, so grant
        // this browser session access to it directly (see Order::isViewableInThisSession()).
        if (! Auth::check()) {
            $request->session()->push('guest_order_ids', $order->id);
        }

        return redirect()->route('checkout.pay', $order);
    }

    /**
     * SumUp's Checkout API has no generic hosted redirect page to send the
     * shopper to — it's designed around embedding SumUp's own card widget on
     * a page of ours, referencing the checkout id created in store() above.
     * The widget's own onResponse callback sends the shopper on to
     * checkout.return once it reports success.
     */
    public function pay(Order $order): View
    {
        abort_unless($order->isViewableInThisSession(), 403);

        return view('shop.checkout.pay', ['order' => $order]);
    }

    /**
     * Double duty, both triggered by the same URL (SumUp's Checkout API has
     * no separately configured webhook — it POSTs the status-changed
     * notification to the checkout's own `return_url`, which is this route):
     *
     *  - POST is SumUp's server-to-server status notification. It carries no
     *    session, so it can't be gated by isViewableInThisSession() — instead
     *    it re-verifies the checkout against SumUp directly (never trusting
     *    this request's body) before touching the order.
     *  - GET is the shopper's own browser, sent here by the card widget's
     *    onResponse callback once it reports success. That alone doesn't mean
     *    the payment succeeded (SumUp's own docs say so) — this just shows a
     *    holding page reflecting whatever the order's status already is by
     *    the time this loads, which the POST above may or may not have
     *    updated yet.
     */
    public function returnFromSumUp(Request $request, Order $order, SumUpCheckoutStatusSync $sync): View|JsonResponse
    {
        if ($request->isMethod('post')) {
            $sync->sync($order);

            return response()->json(['message' => 'ok']);
        }

        abort_unless($order->isViewableInThisSession(), 403);

        return view('shop.checkout.return', ['order' => $order->fresh()]);
    }
}
