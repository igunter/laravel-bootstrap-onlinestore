<?php

namespace App\Support;

use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Cart, keyed by variant id (one row per variant — adding an already-present
 * variant increments its quantity rather than duplicating the row).
 * Everything needed to display a row (name/sku/options label/price/image) is
 * snapshotted at add time, so later admin edits to the product/variant don't
 * retroactively change what's already sitting in someone's cart.
 *
 * Guests get a session-backed cart; a logged-in user's cart lives in the
 * `cart_items` table instead, so it survives across devices/sessions. A
 * guest's session cart is merged into their database cart automatically on
 * login (see the `Login` event listener registered in AppServiceProvider).
 */
class Cart
{
    private const SESSION_KEY = 'cart';

    /**
     * @return Collection<string, array{
     *     variant_id: int,
     *     product_id: int,
     *     product_slug: string,
     *     name: string,
     *     sku: string,
     *     options_label: string,
     *     unit_price: float,
     *     quantity: int,
     *     image_url: string|null,
     * }>
     */
    public function items(): Collection
    {
        if (Auth::check()) {
            return $this->dbItemsQuery()->get()->mapWithKeys(fn (CartItem $item) => [
                (string) $item->product_variant_id => $this->itemFromModel($item),
            ]);
        }

        return collect($this->rawSessionItems());
    }

    public function add(ProductVariant $variant, int $quantity): void
    {
        if (Auth::check()) {
            $current = $this->dbItemsQuery()->where('product_variant_id', $variant->id)->first();
            $newQuantity = $this->capToStock($variant, ($current->quantity ?? 0) + max(1, $quantity));

            if ($newQuantity < 1) {
                return;
            }

            $snapshot = $this->snapshot($variant);

            CartItem::updateOrCreate(
                ['user_id' => Auth::id(), 'product_variant_id' => $variant->id],
                [
                    'product_id' => $snapshot['product_id'],
                    'product_name' => $snapshot['name'],
                    'product_slug' => $snapshot['product_slug'],
                    'variant_sku' => $snapshot['sku'],
                    'options_label' => $snapshot['options_label'],
                    'unit_price' => $snapshot['unit_price'],
                    'image_url' => $snapshot['image_url'],
                    'quantity' => $newQuantity,
                ],
            );

            return;
        }

        $rowId = (string) $variant->id;
        $items = $this->rawSessionItems();

        $currentQuantity = $items[$rowId]['quantity'] ?? 0;
        $newQuantity = $this->capToStock($variant, $currentQuantity + max(1, $quantity));

        if ($newQuantity < 1) {
            return;
        }

        $items[$rowId] = [
            'variant_id' => $variant->id,
            ...$this->snapshot($variant),
            'quantity' => $newQuantity,
        ];

        $this->saveSession($items);
    }

    public function update(string $rowId, int $quantity): void
    {
        if (Auth::check()) {
            $item = $this->dbItemsQuery()->where('product_variant_id', $rowId)->first();

            if (! $item) {
                return;
            }

            $variant = ProductVariant::find($item->product_variant_id);
            $cappedQuantity = $variant ? $this->capToStock($variant, $quantity) : $quantity;

            if ($cappedQuantity < 1) {
                $item->delete();
            } else {
                $item->update(['quantity' => $cappedQuantity]);
            }

            return;
        }

        $items = $this->rawSessionItems();

        if (! isset($items[$rowId])) {
            return;
        }

        $variant = ProductVariant::find($items[$rowId]['variant_id']);
        $cappedQuantity = $variant ? $this->capToStock($variant, $quantity) : $quantity;

        if ($cappedQuantity < 1) {
            unset($items[$rowId]);
        } else {
            $items[$rowId]['quantity'] = $cappedQuantity;
        }

        $this->saveSession($items);
    }

    public function remove(string $rowId): void
    {
        if (Auth::check()) {
            $this->dbItemsQuery()->where('product_variant_id', $rowId)->delete();

            return;
        }

        $items = $this->rawSessionItems();
        unset($items[$rowId]);
        $this->saveSession($items);
    }

    public function clear(): void
    {
        if (Auth::check()) {
            $this->dbItemsQuery()->delete();

            return;
        }

        Session::forget(self::SESSION_KEY);
    }

    public function subtotal(): float
    {
        return (float) $this->items()->sum(fn (array $item) => $item['unit_price'] * $item['quantity']);
    }

    public function count(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    /**
     * Folds a just-logged-in guest's session cart into their database cart —
     * quantities are combined (capped at current stock) rather than the
     * database rows being clobbered, since either side could have items the
     * other doesn't.
     */
    public function mergeSessionIntoDatabase(): void
    {
        $sessionItems = $this->rawSessionItems();

        if (empty($sessionItems)) {
            return;
        }

        foreach ($sessionItems as $item) {
            $variant = ProductVariant::find($item['variant_id']);

            if ($variant) {
                $this->add($variant, $item['quantity']);
            }
        }

        Session::forget(self::SESSION_KEY);
    }

    private function capToStock(ProductVariant $variant, int $quantity): int
    {
        return min($quantity, max(0, $variant->stock_quantity));
    }

    /**
     * @return array{product_id: int, product_slug: string, name: string, sku: string, options_label: string, unit_price: float, image_url: string|null}
     */
    private function snapshot(ProductVariant $variant): array
    {
        return [
            'product_id' => $variant->product_id,
            'product_slug' => $variant->product->slug,
            'name' => $variant->product->name,
            'sku' => $variant->sku,
            'options_label' => $variant->optionsLabel(),
            'unit_price' => (float) $variant->effective_price,
            // The variant's own image if it has one (e.g. a colour-specific
            // shot), otherwise the parent product's.
            'image_url' => $variant->getFirstMediaUrl('images', 'thumb')
                ?: $variant->product->getFirstMediaUrl('images', 'thumb')
                ?: null,
        ];
    }

    /**
     * @return array{variant_id: int, product_id: int, product_slug: string, name: string, sku: string, options_label: string, unit_price: float, quantity: int, image_url: string|null}
     */
    private function itemFromModel(CartItem $item): array
    {
        return [
            'variant_id' => $item->product_variant_id,
            'product_id' => $item->product_id,
            'product_slug' => $item->product_slug,
            'name' => $item->product_name,
            'sku' => $item->variant_sku,
            'options_label' => $item->options_label ?? '',
            'unit_price' => (float) $item->unit_price,
            'quantity' => $item->quantity,
            'image_url' => $item->image_url,
        ];
    }

    private function dbItemsQuery(): Builder
    {
        return CartItem::where('user_id', Auth::id());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function rawSessionItems(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    private function saveSession(array $items): void
    {
        Session::put(self::SESSION_KEY, $items);
    }
}
