@extends('layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Order {{ $order->order_number }}</h1>
        <span class="badge text-bg-{{ $order->status->badgeVariant() }} fs-6">{{ $order->status->label() }}</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Items</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Options</th>
                                <th>Unit price</th>
                                <th>Qty</th>
                                <th>Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-muted">{{ $item->variant_options_label ?: '—' }}</td>
                                    <td>£{{ number_format($item->unit_price, 2) }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>£{{ number_format($item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-end">
                    <div>Subtotal: £{{ number_format($order->subtotal, 2) }}</div>
                    <div class="fs-5 fw-semibold">Total: £{{ number_format($order->total, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Shipping &amp; contact</div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $order->contact_name }}</strong></p>
                    <p class="mb-1">{{ $order->contact_email }}</p>
                    <p class="mb-0">
                        {{ $order->shipping_address_line1 }}<br>
                        @if ($order->shipping_address_line2)
                            {{ $order->shipping_address_line2 }}<br>
                        @endif
                        {{ $order->shipping_city }}, {{ $order->shipping_postcode }}<br>
                        {{ $order->shipping_country }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
