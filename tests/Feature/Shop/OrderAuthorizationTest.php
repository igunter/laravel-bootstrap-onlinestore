<?php

namespace Tests\Feature\Shop;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->for($owner)->create();

        $this->actingAs($other)->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_customer_can_view_their_own_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->for($owner)->create();

        $this->actingAs($owner)->get(route('orders.show', $order))->assertOk();
    }

    public function test_guest_is_redirected_to_login_when_viewing_order_history(): void
    {
        $this->get(route('orders.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_view_a_customers_order(): void
    {
        $order = Order::factory()->create();

        $this->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_guest_cannot_view_another_guests_order_without_the_session_flag(): void
    {
        $order = Order::factory()->create(['user_id' => null]);

        $this->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_customer_only_sees_their_own_orders_in_index(): void
    {
        $owner = User::factory()->create();
        Order::factory()->for($owner)->create(['order_number' => 'ORD-MINE1234']);
        Order::factory()->create(['order_number' => 'ORD-OTHER123']);

        $response = $this->actingAs($owner)->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee('ORD-MINE1234');
        $response->assertDontSee('ORD-OTHER123');
    }

    public function test_admin_can_view_any_order(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create();

        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk();
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create();

        $this->actingAs($customer)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.orders.show', $order))->assertForbidden();
    }
}
