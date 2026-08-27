@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    <h1 class="h4 mb-3">Orders</h1>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th class="d-none d-md-table-cell">Placed</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->contact_name }}</td>
                            <td class="d-none d-md-table-cell">{{ $order->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <span class="badge text-bg-{{ $order->status->badgeVariant() }}">{{ $order->status->label() }}</span>
                            </td>
                            <td>£{{ number_format($order->total, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i><span class="d-none d-md-inline ms-1">View</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
