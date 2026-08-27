<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'total',
        'currency',
        'contact_name',
        'contact_email',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_postcode',
        'shipping_country',
        'sumup_checkout_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * A short, unique, human-readable reference (e.g. "ORD-7F3K9QZP") — used as
     * the customer-facing order reference and, from Phase 7 on, the SumUp
     * `checkout_reference`.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.strtoupper(Str::random(8));
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Whether the current request (logged-in owner, or — for a guest order —
     * the browser session that placed it, per the `guest_order_ids` flag
     * CheckoutController sets at checkout) may view this order. Shared by the
     * customer order/checkout-return pages; admins use a separate route.
     */
    public function isViewableInThisSession(): bool
    {
        if ($this->user_id !== null) {
            return $this->user_id === auth()->id();
        }

        return in_array($this->id, session('guest_order_ids', []), true);
    }
}
