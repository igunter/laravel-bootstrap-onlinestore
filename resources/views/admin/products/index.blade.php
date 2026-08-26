@extends('layouts.admin')

@section('title', 'Products')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Products</h1>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>New Product
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="d-none d-md-table-cell">Category</th>
                        <th class="d-none d-md-table-cell">Brand</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td class="d-none d-md-table-cell">{{ $product->category?->name ?? '—' }}</td>
                            <td class="d-none d-md-table-cell">{{ $product->brand?->name ?? '—' }}</td>
                            <td>£{{ number_format($product->base_price, 2) }}</td>
                            <td>
                                @if ($product->is_active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <x-row-actions
                                    :edit-url="route('admin.products.edit', $product)"
                                    :delete-url="route('admin.products.destroy', $product)"
                                    :delete-label="$product->name"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No products yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
