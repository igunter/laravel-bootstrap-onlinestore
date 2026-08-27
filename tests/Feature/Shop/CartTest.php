<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_the_same_variant_twice_increments_quantity_not_duplicate_rows(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 2]);
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 3]);

        $response = $this->get(route('cart.index'));

        $response->assertOk();
        $response->assertViewHas('items', function ($items) use ($variant) {
            return $items->count() === 1 && $items->get((string) $variant->id)['quantity'] === 5;
        });
    }

    public function test_quantity_is_capped_at_available_stock_when_adding(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 3]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 10]);

        $response = $this->get(route('cart.index'));

        $response->assertViewHas('items', function ($items) use ($variant) {
            return $items->get((string) $variant->id)['quantity'] === 3;
        });
    }

    public function test_update_returns_json_for_ajax_requests(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['price' => 10, 'stock_quantity' => 5]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->patchJson(route('cart.update', $variant->id), ['quantity' => 3]);

        $response->assertOk();
        $response->assertJson([
            'removed' => false,
            'quantity' => 3,
            'line_total' => 30.0,
            'subtotal' => 30.0,
            'cart_count' => 3,
        ]);
    }

    public function test_ajax_update_caps_quantity_at_stock_and_reports_it_back(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['price' => 2, 'stock_quantity' => 4]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->patchJson(route('cart.update', $variant->id), ['quantity' => 100]);

        $response->assertOk();
        $response->assertJson(['quantity' => 4, 'removed' => false]);
    }

    public function test_ajax_update_to_zero_reports_the_row_as_removed(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 5]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->patchJson(route('cart.update', $variant->id), ['quantity' => 0]);

        $response->assertOk();
        $response->assertJson(['removed' => true, 'cart_count' => 0]);
    }

    public function test_quantity_is_capped_at_available_stock_when_updating(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 5]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 2]);
        $this->patch(route('cart.update', $variant->id), ['quantity' => 100]);

        $response = $this->get(route('cart.index'));

        $response->assertViewHas('items', function ($items) use ($variant) {
            return $items->get((string) $variant->id)['quantity'] === 5;
        });
    }

    public function test_subtotal_math_is_correct(): void
    {
        $variantA = ProductVariant::factory()->for(Product::factory())->create(['price' => 10, 'stock_quantity' => 10]);
        $variantB = ProductVariant::factory()->for(Product::factory())->create(['price' => 4.5, 'stock_quantity' => 10]);

        $this->post(route('cart.store'), ['product_variant_id' => $variantA->id, 'quantity' => 2]);
        $this->post(route('cart.store'), ['product_variant_id' => $variantB->id, 'quantity' => 3]);

        $response = $this->get(route('cart.index'));

        // (10 * 2) + (4.5 * 3) = 33.5
        $response->assertViewHas('subtotal', 33.5);
    }

    public function test_updating_quantity_to_zero_removes_the_row(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->patch(route('cart.update', $variant->id), ['quantity' => 0]);

        $response = $this->get(route('cart.index'));

        $response->assertViewHas('items', fn ($items) => $items->isEmpty());
    }

    public function test_item_can_be_removed(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->delete(route('cart.destroy', $variant->id));

        $response = $this->get(route('cart.index'));

        $response->assertViewHas('items', fn ($items) => $items->isEmpty());
    }

    public function test_cart_can_be_cleared(): void
    {
        $variantA = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);
        $variantB = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->post(route('cart.store'), ['product_variant_id' => $variantA->id, 'quantity' => 1]);
        $this->post(route('cart.store'), ['product_variant_id' => $variantB->id, 'quantity' => 1]);
        $this->delete(route('cart.clear'));

        $response = $this->get(route('cart.index'));

        $response->assertViewHas('items', fn ($items) => $items->isEmpty());
    }

    public function test_out_of_stock_variant_cannot_be_added(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 0]);

        $response = $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $response->assertSessionHas('error');

        $this->get(route('cart.index'))->assertViewHas('items', fn ($items) => $items->isEmpty());
    }

    public function test_inactive_variant_cannot_be_added(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['is_active' => false, 'stock_quantity' => 10]);

        $response = $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $response->assertSessionHas('error');

        $this->get(route('cart.index'))->assertViewHas('items', fn ($items) => $items->isEmpty());
    }

    public function test_variant_belonging_to_inactive_product_cannot_be_added(): void
    {
        $product = Product::factory()->create(['is_active' => false]);
        $variant = ProductVariant::factory()->for($product)->create(['stock_quantity' => 10]);

        $response = $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $response->assertSessionHas('error');

        $this->get(route('cart.index'))->assertViewHas('items', fn ($items) => $items->isEmpty());
    }

    public function test_cart_row_snapshots_options_label_and_price_at_add_time(): void
    {
        $product = Product::factory()->create(['name' => 'Hoodie']);
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'HOODIE-L', 'price' => 25, 'stock_quantity' => 10]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        // Price changes after the item is already in the cart shouldn't affect it.
        $variant->update(['price' => 999]);

        $response = $this->get(route('cart.index'));

        $response->assertViewHas('items', function ($items) use ($variant) {
            $item = $items->get((string) $variant->id);

            return $item['name'] === 'Hoodie'
                && $item['sku'] === 'HOODIE-L'
                && $item['unit_price'] === 25.0;
        });
    }
}
