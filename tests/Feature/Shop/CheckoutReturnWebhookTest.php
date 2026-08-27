<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Notifications\OrderPaid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * SumUp's Checkout API has no separately configured webhook URL — it POSTs
 * the status-changed notification to the checkout's own `return_url`, i.e.
 * this exact route (see CheckoutController::returnFromSumUp). This is the
 * mechanism SumUp actually uses in production; SumUpWebhookTest covers the
 * secondary /api/webhooks/sumup fallback that isn't really called for this
 * integration.
 */
class CheckoutReturnWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSumUpStatus(string $checkoutId, string $status): void
    {
        Http::fake([
            "https://api.sumup.com/v0.1/checkouts/{$checkoutId}" => Http::response(['id' => $checkoutId, 'status' => $status]),
        ]);
    }

    public function test_post_marks_order_paid_and_decrements_stock(): void
    {
        Notification::fake();

        $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'sumup_checkout_id' => 'checkout-abc',
            'contact_email' => 'buyer@example.com',
        ]);
        OrderItem::factory()->for($order)->create([
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $this->fakeSumUpStatus('checkout-abc', 'PAID');

        // No session at all here — deliberately not using actingAs or any
        // prior request, matching how SumUp's server-to-server POST actually
        // arrives. Also spoofs a "status" field in the body, to prove it's
        // ignored in favour of what SumUp itself reports back.
        $response = $this->postJson(route('checkout.return', $order), [
            'event_type' => 'CHECKOUT_STATUS_CHANGED',
            'id' => 'checkout-abc',
            'status' => 'FAILED',
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(7, $variant->fresh()->stock_quantity);

        Notification::assertSentOnDemand(
            OrderPaid::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'buyer@example.com'
        );
    }

    public function test_post_is_idempotent_and_does_not_double_decrement_stock(): void
    {
        $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'sumup_checkout_id' => 'checkout-abc',
        ]);
        OrderItem::factory()->for($order)->create([
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $this->fakeSumUpStatus('checkout-abc', 'PAID');

        $this->postJson(route('checkout.return', $order), ['id' => 'checkout-abc'])->assertOk();
        $this->postJson(route('checkout.return', $order), ['id' => 'checkout-abc'])->assertOk();

        $this->assertSame(7, $variant->fresh()->stock_quantity);
    }

    public function test_post_marks_order_failed(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'sumup_checkout_id' => 'checkout-xyz',
        ]);

        $this->fakeSumUpStatus('checkout-xyz', 'FAILED');

        $this->postJson(route('checkout.return', $order), ['id' => 'checkout-xyz'])->assertOk();

        $this->assertSame(OrderStatus::Failed, $order->fresh()->status);
    }

    public function test_post_is_not_gated_by_session_ownership(): void
    {
        // Unlike the GET holding page, the POST notification carries no
        // session at all (it's server-to-server), so it must not be blocked
        // by isViewableInThisSession() the way GET is.
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'sumup_checkout_id' => 'checkout-xyz',
            'user_id' => null,
        ]);

        $this->fakeSumUpStatus('checkout-xyz', 'PAID');

        $this->postJson(route('checkout.return', $order), ['id' => 'checkout-xyz'])->assertOk();

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }

    public function test_get_still_requires_session_ownership(): void
    {
        $order = Order::factory()->create();

        $this->get(route('checkout.return', $order))->assertForbidden();
    }
}
