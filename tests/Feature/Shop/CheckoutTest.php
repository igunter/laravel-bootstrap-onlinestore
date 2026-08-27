<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function validCheckoutData(array $overrides = []): array
    {
        return array_merge([
            'contact_name' => 'Jane Shopper',
            'contact_email' => 'jane@example.com',
            'shipping_address_line1' => '221B Baker Street',
            'shipping_address_line2' => null,
            'shipping_city' => 'London',
            'shipping_postcode' => 'NW1 6XE',
            'shipping_country' => 'United Kingdom',
        ], $overrides);
    }

    public function test_guest_can_check_out_without_an_account(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->post(route('checkout.store'), $this->validCheckoutData());

        $order = Order::first();

        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $response->assertRedirect(route('orders.show', $order));
    }

    public function test_guest_can_view_the_order_they_just_placed(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('checkout.store'), $this->validCheckoutData());

        $order = Order::first();

        // Same browser session that placed it — no login involved.
        $this->get(route('orders.show', $order))->assertOk();
    }

    public function test_order_and_items_are_created_correctly_from_the_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Hoodie']);
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'HOODIE-L', 'price' => 25, 'stock_quantity' => 10]);

        $this->actingAs($user)->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 2]);

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->validCheckoutData());

        $order = Order::first();

        $this->assertNotNull($order);
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertEquals(50.0, (float) $order->subtotal);
        $this->assertEquals(50.0, (float) $order->total);
        $this->assertSame('Jane Shopper', $order->contact_name);

        $this->assertSame(1, $order->items()->count());
        $item = $order->items()->first();
        $this->assertSame('Hoodie', $item->product_name);
        $this->assertSame('HOODIE-L', $item->variant_sku);
        $this->assertSame(2, $item->quantity);
        $this->assertEquals(25.0, (float) $item->unit_price);
        $this->assertEquals(50.0, (float) $item->line_total);
    }

    public function test_cart_is_cleared_after_placing_an_order(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->actingAs($user)->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->actingAs($user)->post(route('checkout.store'), $this->validCheckoutData());

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertViewHas('items', fn ($items) => $items->isEmpty());
    }

    public function test_checkout_show_redirects_to_cart_when_cart_is_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('checkout.show'));

        $response->assertRedirect(route('cart.index'));
    }

    public function test_checkout_store_redirects_to_cart_when_cart_is_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->validCheckoutData());

        $response->assertRedirect(route('cart.index'));
        $this->assertSame(0, Order::count());
    }

    public function test_checkout_rejects_order_when_stock_ran_out_since_adding_to_cart(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 5]);

        $this->actingAs($user)->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 5]);

        // Stock sold out elsewhere after the item was added to this cart.
        $variant->update(['stock_quantity' => 0]);

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->validCheckoutData());

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error');
        $this->assertSame(0, Order::count());
    }

    public function test_checkout_requires_shipping_fields(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->actingAs($user)->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->actingAs($user)->post(route('checkout.store'), []);

        $response->assertSessionHasErrors([
            'contact_name', 'contact_email', 'shipping_address_line1', 'shipping_city', 'shipping_postcode', 'shipping_country',
        ]);
        $this->assertSame(0, Order::count());
    }
}
