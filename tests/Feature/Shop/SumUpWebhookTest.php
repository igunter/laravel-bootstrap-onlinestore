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

class SumUpWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSumUpStatus(string $checkoutId, string $status): void
    {
        Http::fake([
            "https://api.sumup.com/v0.1/checkouts/{$checkoutId}" => Http::response(['id' => $checkoutId, 'status' => $status]),
        ]);
    }

    public function test_webhook_marks_order_paid_and_decrements_stock(): void
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

        // Deliberately post a spoofed "status" in the body too, to prove the
        // handler ignores it and only trusts what SumUp itself reports back.
        $response = $this->postJson(route('webhooks.sumup'), [
            'checkout_id' => 'checkout-abc',
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

    public function test_webhook_is_idempotent_and_does_not_double_decrement_stock(): void
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

        $this->postJson(route('webhooks.sumup'), ['checkout_id' => 'checkout-abc'])->assertOk();
        $this->postJson(route('webhooks.sumup'), ['checkout_id' => 'checkout-abc'])->assertOk();

        $this->assertSame(7, $variant->fresh()->stock_quantity);
    }

    public function test_webhook_marks_order_failed(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'sumup_checkout_id' => 'checkout-xyz',
        ]);

        $this->fakeSumUpStatus('checkout-xyz', 'FAILED');

        $this->postJson(route('webhooks.sumup'), ['checkout_id' => 'checkout-xyz'])->assertOk();

        $this->assertSame(OrderStatus::Failed, $order->fresh()->status);
    }

    public function test_webhook_for_unknown_checkout_id_is_safely_ignored(): void
    {
        $response = $this->postJson(route('webhooks.sumup'), ['checkout_id' => 'does-not-exist']);

        $response->assertOk();
        $this->assertSame(0, Order::count());
    }

    public function test_webhook_without_a_checkout_id_is_rejected(): void
    {
        $this->postJson(route('webhooks.sumup'), [])->assertStatus(422);
    }
}
