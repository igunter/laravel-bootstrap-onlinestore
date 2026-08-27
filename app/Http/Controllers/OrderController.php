<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('shop.orders.index', [
            'orders' => auth()->user()->orders()->latest()->paginate(10),
        ]);
    }

    public function show(Order $order): View
    {
        abort_unless($this->canView($order), 403);

        return view('shop.orders.show', [
            'order' => $order->load('items'),
        ]);
    }

    /**
     * A logged-in order belongs to whoever placed it; a guest order (no
     * account attached) is only viewable by the browser session that placed
     * it, per the "guest_order_ids" flag CheckoutController sets at checkout.
     */
    private function canView(Order $order): bool
    {
        if ($order->user_id !== null) {
            return $order->user_id === auth()->id();
        }

        return in_array($order->id, session('guest_order_ids', []), true);
    }
}
