@extends('layouts.app')

@section('title', 'Your Orders')

@section('content')
    <h1 class="h3 mb-4">Your Orders</h1>

    @if ($orders->isEmpty())
        <p class="text-muted">
            You haven't placed any orders yet. <a href="{{ route('shop.products.index') }}">Start shopping</a>.
        </p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th class="d-none d-md-table-cell">Placed</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td class="d-none d-md-table-cell">{{ $order->created_at->format('d M Y') }}</td>
                            <td>
                                <span class="badge text-bg-{{ $order->status->badgeVariant() }}">{{ $order->status->label() }}</span>
                            </td>
                            <td>£{{ number_format($order->total, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    @endif
@endsection
