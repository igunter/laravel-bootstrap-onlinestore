<?php

namespace Tests\Feature\Shop;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_logged_in_users_cart_is_saved_to_the_database(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->actingAs($user)->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 2]);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_a_logged_in_users_cart_is_not_stored_in_the_session(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->actingAs($user)->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->assertEmpty(session('cart', []));
    }

    public function test_updating_and_removing_items_works_for_a_logged_in_users_database_cart(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->actingAs($user)->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->actingAs($user)->patch(route('cart.update', $variant->id), ['quantity' => 5]);

        $this->assertDatabaseHas('cart_items', ['user_id' => $user->id, 'quantity' => 5]);

        $this->actingAs($user)->delete(route('cart.destroy', $variant->id));

        $this->assertDatabaseMissing('cart_items', ['user_id' => $user->id, 'product_variant_id' => $variant->id]);
    }

    public function test_clearing_the_cart_removes_the_database_rows(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->actingAs($user)->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->actingAs($user)->delete(route('cart.clear'));

        $this->assertSame(0, CartItem::where('user_id', $user->id)->count());
    }

    public function test_guest_cart_stays_out_of_the_database(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->assertSame(0, CartItem::count());
        $this->assertNotEmpty(session('cart', []));
    }

    /**
     * Logs in via the real login endpoint (not actingAs()) so the framework's
     * own Auth::attempt()/login() actually dispatches the Login event within
     * a normal request/response cycle — actingAs() sets the resolved user
     * directly and never fires it, which is exactly what Cart's merge-on-login
     * behavior depends on.
     */
    private function login(User $user): void
    {
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();
    }

    public function test_guest_session_cart_is_merged_into_the_database_on_login(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 2]);
        $this->assertNotEmpty(session('cart', []));

        $this->login($user);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        $this->assertEmpty(session('cart', []));
    }

    public function test_merging_combines_quantities_with_an_existing_database_cart_row(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 10]);

        // Already 2 in the database cart from a previous logged-in session.
        CartItem::create([
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'product_name' => $variant->product->name,
            'product_slug' => $variant->product->slug,
            'variant_sku' => $variant->sku,
            'options_label' => '',
            'unit_price' => $variant->effective_price,
            'quantity' => 2,
        ]);

        // As a guest (e.g. a different browser), 1 more of the same variant.
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->login($user);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
        $this->assertSame(1, CartItem::where('user_id', $user->id)->count());
    }

    public function test_merge_caps_combined_quantity_at_stock(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['stock_quantity' => 4]);

        CartItem::create([
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'product_name' => $variant->product->name,
            'product_slug' => $variant->product->slug,
            'variant_sku' => $variant->sku,
            'options_label' => '',
            'unit_price' => $variant->effective_price,
            'quantity' => 3,
        ]);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'quantity' => 3]);

        $this->login($user);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 4,
        ]);
    }
}
