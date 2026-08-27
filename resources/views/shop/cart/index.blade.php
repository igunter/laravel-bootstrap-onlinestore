@extends('layouts.app')

@section('title', 'Your Cart')

@section('content')
    <h1 class="h3 mb-4">Your Cart</h1>

    <p id="empty-cart-message" class="text-muted @if ($items->isNotEmpty()) d-none @endif">
        Your cart is empty. <a href="{{ route('shop.products.index') }}">Continue shopping</a>.
    </p>

    <div id="cart-contents" class="@if ($items->isEmpty()) d-none @endif">
        {{-- Desktop/tablet: a plain table. --}}
        <div class="d-none d-md-block table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th></th>
                        <th>Product</th>
                        <th class="text-center" style="width: 140px;">Quantity</th>
                        <th class="text-end">Unit price</th>
                        <th class="text-end">Line total</th>
                        <th style="width: 60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $rowId => $item)
                        <tr data-row-id="{{ $rowId }}">
                            <td style="width: 64px;">
                                <a href="{{ route('shop.products.show', $item['product_slug']) }}">
                                    @if ($item['image_url'] ?? null)
                                        <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="rounded" style="width: 56px; height: 56px; object-fit: cover;">
                                    @else
                                        <span class="d-flex align-items-center justify-content-center bg-light rounded text-muted" style="width: 56px; height: 56px;">
                                            <i class="bi bi-image"></i>
                                        </span>
                                    @endif
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('shop.products.show', $item['product_slug']) }}">{{ $item['name'] }}</a>
                                @if ($item['options_label'])
                                    <div class="text-muted small">{{ $item['options_label'] }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="input-group quantity-stepper mx-auto" style="width: auto;">
                                    <button type="button" class="btn btn-outline-secondary quantity-step" data-step="-1">−</button>
                                    <input type="number" class="form-control text-center quantity-input" value="{{ $item['quantity'] }}" min="1" data-row-id="{{ $rowId }}" data-update-url="{{ route('cart.update', $rowId) }}">
                                    <button type="button" class="btn btn-outline-secondary quantity-step" data-step="1">+</button>
                                </div>
                            </td>
                            <td class="text-end">£{{ number_format($item['unit_price'], 2) }}</td>
                            <td class="line-total text-end" data-row-id="{{ $rowId }}">£{{ number_format($item['unit_price'] * $item['quantity'], 2) }}</td>
                            <td class="text-end">
                                <form action="{{ route('cart.destroy', $rowId) }}" method="POST" onsubmit="return confirm('Remove this item?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile: stacked cards instead of a cramped, overflowing table. --}}
        <div class="d-md-none d-flex flex-column gap-3">
            @foreach ($items as $rowId => $item)
                <div class="card" data-row-id="{{ $rowId }}">
                    <div class="card-body">
                        <div class="d-flex gap-2 mb-3">
                            <a href="{{ route('shop.products.show', $item['product_slug']) }}" class="flex-shrink-0">
                                @if ($item['image_url'] ?? null)
                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="rounded" style="width: 56px; height: 56px; object-fit: cover;">
                                @else
                                    <span class="d-flex align-items-center justify-content-center bg-light rounded text-muted" style="width: 56px; height: 56px;">
                                        <i class="bi bi-image"></i>
                                    </span>
                                @endif
                            </a>

                            <div>
                                <a href="{{ route('shop.products.show', $item['product_slug']) }}" class="fw-semibold">{{ $item['name'] }}</a>
                                @if ($item['options_label'])
                                    <div class="text-muted small">{{ $item['options_label'] }}</div>
                                @endif
                                <div class="text-muted small">£{{ number_format($item['unit_price'], 2) }} each</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="input-group input-group-sm quantity-stepper" style="width: auto;">
                                <button type="button" class="btn btn-outline-secondary quantity-step" data-step="-1">−</button>
                                <input type="number" class="form-control text-center quantity-input" value="{{ $item['quantity'] }}" min="1" data-row-id="{{ $rowId }}" data-update-url="{{ route('cart.update', $rowId) }}">
                                <button type="button" class="btn btn-outline-secondary quantity-step" data-step="1">+</button>
                            </div>

                            <div class="d-flex align-items-center gap-3">
                                <div class="text-end">
                                    <div class="text-muted small">Line total</div>
                                    <div class="fs-5 fw-semibold line-total" data-row-id="{{ $rowId }}">£{{ number_format($item['unit_price'] * $item['quantity'], 2) }}</div>
                                </div>

                                <form action="{{ route('cart.destroy', $rowId) }}" method="POST" onsubmit="return confirm('Remove this item?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-start border-top pt-3 mt-3 gap-3">
            <div class="order-2 order-sm-1">
                <form action="{{ route('cart.clear') }}" method="POST" onsubmit="return confirm('Clear your cart?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Clear cart</button>
                </form>
            </div>

            <div class="order-1 order-sm-2 text-sm-end">
                <p class="fs-4 mb-2">Subtotal: £<span id="cart-subtotal">{{ number_format($subtotal, 2) }}</span></p>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <a href="{{ route('shop.products.index') }}" class="btn btn-outline-secondary">Continue shopping</a>
                    <a href="{{ route('checkout.show') }}" class="btn btn-primary">Checkout<i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    @push('head')
        <style>
            .quantity-input {
                -moz-appearance: textfield;
            }

            .quantity-input::-webkit-outer-spin-button,
            .quantity-input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            @media (max-width: 767.98px) {
                /* Bootstrap sets `.input-group > .form-control { min-width: 0 }` so
                   inputs can shrink inside an input-group — that selector out-specifies
                   a plain `.quantity-input` class, so it has to be matched here too. */
                .input-group .quantity-input {
                    min-width: 3rem;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                // Sized in `ch` units (roughly one digit wide each), so the box only
                // ever needs to be as wide as the number currently inside it.
                const sizeToContent = (input) => {
                    input.style.width = `${Math.max(String(input.value).length, 1) + 2}ch`;
                };
                const cartContents = document.getElementById('cart-contents');
                const emptyMessage = document.getElementById('empty-cart-message');
                const subtotalEl = document.getElementById('cart-subtotal');

                // Each cart row is rendered twice — once in the desktop table, once
                // in the mobile card list — so every update has to be applied to
                // both copies (identified by the shared data-row-id) rather than
                // just the one the shopper actually interacted with.
                const applyResponse = (rowId, data) => {
                    if (data.removed) {
                        document.querySelectorAll(`[data-row-id="${rowId}"]`).forEach((el) => el.remove());

                        if (!document.querySelector('[data-row-id]')) {
                            cartContents.classList.add('d-none');
                            emptyMessage.classList.remove('d-none');
                        }
                    } else {
                        document.querySelectorAll(`.quantity-input[data-row-id="${rowId}"]`).forEach((el) => {
                            el.value = data.quantity;
                            sizeToContent(el);
                        });

                        document.querySelectorAll(`.line-total[data-row-id="${rowId}"]`).forEach((el) => {
                            el.textContent = `£${data.line_total.toFixed(2)}`;
                        });
                    }

                    if (subtotalEl) subtotalEl.textContent = data.subtotal.toFixed(2);

                    document.querySelectorAll('.cart-count-badge').forEach((badge) => {
                        badge.textContent = data.cart_count;
                        badge.classList.toggle('d-none', data.cart_count < 1);
                    });

                    document.querySelectorAll('.cart-indicator').forEach((indicator) => {
                        indicator.classList.toggle('d-none', data.cart_count < 1);
                    });
                };

                const updateQuantity = (input) => {
                    const min = Number(input.min || 1);
                    if (Number(input.value) < min) input.value = min;

                    sizeToContent(input);
                    input.disabled = true;

                    fetch(input.dataset.updateUrl, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ quantity: Number(input.value) }),
                    })
                        .then((response) => response.json())
                        .then((data) => applyResponse(input.dataset.rowId, data))
                        .finally(() => { input.disabled = false; });
                };

                document.querySelectorAll('.quantity-step').forEach((button) => {
                    button.addEventListener('click', () => {
                        const input = button.parentElement.querySelector('.quantity-input');
                        const min = Number(input.min || 1);
                        const next = Number(input.value || 0) + Number(button.dataset.step);

                        input.value = Math.max(min, next);
                        updateQuantity(input);
                    });
                });

                document.querySelectorAll('.quantity-input').forEach((input) => {
                    sizeToContent(input);
                    input.addEventListener('input', () => sizeToContent(input));
                    input.addEventListener('change', () => updateQuantity(input));
                });
            })();
        </script>
    @endpush
@endsection
