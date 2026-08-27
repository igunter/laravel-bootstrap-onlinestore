<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Cart;
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

    public function store(Request $request, Cart $cart): RedirectResponse
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

        $order = DB::transaction(function () use ($data, $items) {
            // Built from the cart's own snapshot, not re-queried live prices —
            // what the shopper saw at checkout is what they're charged.
            $subtotal = $items->sum(fn (array $item) => $item['unit_price'] * $item['quantity']);

            $order = Order::create([
                ...$data,
                'user_id' => Auth::id(),
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $subtotal,
                'total' => $subtotal,
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

            return $order;
        });

        $cart->clear();

        // A guest has no account to look this order up against later, so grant
        // this browser session access to it directly (see OrderController::show).
        if (! Auth::check()) {
            $request->session()->push('guest_order_ids', $order->id);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order placed! We\'ll be in touch once it ships.');
    }
}
