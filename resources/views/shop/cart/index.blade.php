@extends('layouts.app')

@section('title', 'Your Cart')

@section('content')
    <h1 class="h3 mb-4">Your Cart</h1>

    <p id="empty-cart-message" class="text-muted @if ($items->isNotEmpty()) d-none @endif">
        Your cart is empty. <a href="{{ route('shop.products.index') }}">Continue shopping</a>.
    </p>

    <div id="cart-contents" class="@if ($items->isEmpty()) d-none @endif">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Options</th>
                        <th>Unit price</th>
                        <th style="width: 140px;">Quantity</th>
                        <th>Line total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cart-rows">
                    @foreach ($items as $rowId => $item)
                        <tr data-row-id="{{ $rowId }}">
                            <td>
                                <a href="{{ route('shop.products.show', $item['product_slug']) }}">{{ $item['name'] }}</a>
                            </td>
                            <td class="text-muted">{{ $item['options_label'] ?: '—' }}</td>
                            <td>£{{ number_format($item['unit_price'], 2) }}</td>
                            <td>
                                <div class="input-group input-group-sm quantity-stepper" style="width: auto;">
                                    <button type="button" class="btn btn-outline-secondary quantity-step" data-step="-1">−</button>
                                    <input type="number" class="form-control text-center quantity-input" value="{{ $item['quantity'] }}" min="1" data-update-url="{{ route('cart.update', $rowId) }}">
                                    <button type="button" class="btn btn-outline-secondary quantity-step" data-step="1">+</button>
                                </div>
                            </td>
                            <td class="line-total">£{{ number_format($item['unit_price'] * $item['quantity'], 2) }}</td>
                            <td>
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

        <div class="d-flex justify-content-between align-items-start border-top pt-3">
            <form action="{{ route('cart.clear') }}" method="POST" onsubmit="return confirm('Clear your cart?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-secondary btn-sm">Clear cart</button>
            </form>

            <div class="text-end">
                <p class="fs-4 mb-2">Subtotal: £<span id="cart-subtotal">{{ number_format($subtotal, 2) }}</span></p>
                <a href="{{ route('shop.products.index') }}" class="btn btn-outline-secondary">Continue shopping</a>
                <a href="{{ route('checkout.show') }}" class="btn btn-primary">Checkout<i class="bi bi-arrow-right ms-1"></i></a>
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

                const applyResponse = (input, data) => {
                    if (data.removed) {
                        input.closest('tr')?.remove();

                        if (!document.querySelector('#cart-rows tr')) {
                            cartContents.classList.add('d-none');
                            emptyMessage.classList.remove('d-none');
                        }
                    } else {
                        input.value = data.quantity;
                        sizeToContent(input);

                        const lineTotalCell = input.closest('tr')?.querySelector('.line-total');
                        if (lineTotalCell) lineTotalCell.textContent = `£${data.line_total.toFixed(2)}`;
                    }

                    if (subtotalEl) subtotalEl.textContent = data.subtotal.toFixed(2);

                    document.querySelectorAll('.cart-count-badge').forEach((badge) => {
                        badge.textContent = data.cart_count;
                        badge.classList.toggle('d-none', data.cart_count < 1);
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
                        .then((data) => applyResponse(input, data))
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
