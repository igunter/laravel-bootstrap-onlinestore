@extends('layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
    <div class="text-center py-5">
        <i class="bi bi-hourglass-split text-primary" style="font-size: 3rem;"></i>
        <h1 class="h3 mt-3">Thanks — we're confirming your payment</h1>
        <p class="text-muted">
            Order <strong>{{ $order->order_number }}</strong> is currently
            <span class="badge text-bg-{{ $order->status->badgeVariant() }}">{{ $order->status->label() }}</span>.
            This can take a few moments to update once payment is confirmed.
        </p>

        <div class="mt-4">
            <a href="{{ route('orders.show', $order) }}" class="btn btn-primary">View order</a>
            <a href="{{ route('shop.products.index') }}" class="btn btn-outline-secondary">Continue shopping</a>
        </div>
    </div>
@endsection
