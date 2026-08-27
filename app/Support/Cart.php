<?php

namespace App\Support;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Session-backed cart, keyed by variant id (one row per variant — adding an
 * already-present variant increments its quantity rather than duplicating the
 * row). Everything needed to display a row (name/sku/options label/price) is
 * snapshotted at add time, so later admin edits to the product/variant don't
 * retroactively change what's already sitting in someone's cart.
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
        return collect($this->rawItems());
    }

    public function add(ProductVariant $variant, int $quantity): void
    {
        $rowId = (string) $variant->id;
        $items = $this->rawItems();

        $currentQuantity = $items[$rowId]['quantity'] ?? 0;
        $newQuantity = $this->capToStock($variant, $currentQuantity + max(1, $quantity));

        if ($newQuantity < 1) {
            return;
        }

        $items[$rowId] = [
            'variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'product_slug' => $variant->product->slug,
            'name' => $variant->product->name,
            'sku' => $variant->sku,
            'options_label' => $variant->optionsLabel(),
            'unit_price' => (float) $variant->effective_price,
            'quantity' => $newQuantity,
            // The variant's own image if it has one (e.g. a colour-specific
            // shot), otherwise the parent product's — snapshotted like
            // everything else above, so later re-ordering/removal of images
            // doesn't change what's already sitting in someone's cart.
            'image_url' => $variant->getFirstMediaUrl('images', 'thumb')
                ?: $variant->product->getFirstMediaUrl('images', 'thumb')
                ?: null,
        ];

        $this->save($items);
    }

    public function update(string $rowId, int $quantity): void
    {
        $items = $this->rawItems();

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

        $this->save($items);
    }

    public function remove(string $rowId): void
    {
        $items = $this->rawItems();
        unset($items[$rowId]);
        $this->save($items);
    }

    public function clear(): void
    {
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

    private function capToStock(ProductVariant $variant, int $quantity): int
    {
        return min($quantity, max(0, $variant->stock_quantity));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function rawItems(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    private function save(array $items): void
    {
        Session::put(self::SESSION_KEY, $items);
    }
}
