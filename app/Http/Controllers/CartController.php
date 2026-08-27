<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Support\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Cart $cart): View
    {
        return view('shop.cart.index', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
        ]);
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::with('product')->findOrFail($data['product_variant_id']);

        if (! $variant->is_active || ! $variant->product->is_active) {
            return back()->with('error', 'This item is not available.');
        }

        if ($variant->stock_quantity < 1) {
            return back()->with('error', 'This item is out of stock.');
        }

        $cart->add($variant, $data['quantity']);

        return back()->with('success', 'Added to cart.');
    }

    public function update(Request $request, string $rowId, Cart $cart): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $cart->update($rowId, $data['quantity']);

        if ($request->wantsJson()) {
            $item = $cart->items()->get($rowId);

            return response()->json([
                'message' => 'Cart updated.',
                'removed' => $item === null,
                'quantity' => $item['quantity'] ?? null,
                'line_total' => $item ? $item['unit_price'] * $item['quantity'] : null,
                'subtotal' => $cart->subtotal(),
                'cart_count' => $cart->count(),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    public function destroy(string $rowId, Cart $cart): RedirectResponse
    {
        $cart->remove($rowId);

        return redirect()->route('cart.index')->with('success', 'Item removed.');
    }

    public function clear(Cart $cart): RedirectResponse
    {
        $cart->clear();

        return redirect()->route('cart.index')->with('success', 'Cart cleared.');
    }
}
