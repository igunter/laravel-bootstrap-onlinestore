@extends('layouts.app')

@section('title', 'Shop')

@section('content')
    <h1 class="h3 {{ $categoryBreadcrumbs->isNotEmpty() ? 'mb-1' : 'mb-4' }}">Shop</h1>

    @if ($categoryBreadcrumbs->isNotEmpty())
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('shop.products.index') }}">Shop</a></li>
                @foreach ($categoryBreadcrumbs as $breadcrumbCategory)
                    @if ($loop->last)
                        <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumbCategory->name }}</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ route('shop.products.index.category', ['category' => $breadcrumbCategory->slug]) }}">{{ $breadcrumbCategory->name }}</a></li>
                    @endif
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="row">
        <div class="col-md-9 order-2 order-md-1">
            @if ($products->isEmpty())
                <p class="text-muted">No products found.</p>
            @else
                <div class="row g-3">
                    @foreach ($products as $product)
                        @include('shop.products._card', ['product' => $product])
                    @endforeach
                </div>

                <div class="mt-4">
                    @include('shop.partials._pagination', [
                        'paginator' => $products,
                        'routeName' => $paginationRouteName,
                        'pagedRouteName' => $paginationPagedRouteName,
                        'routeParams' => $paginationRouteParams,
                    ])
                </div>
            @endif
        </div>

        <div class="col-md-3 order-1 order-md-2 mb-4 mb-md-0">
            <div class="sticky-md-top" style="top: 5rem;">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6 card-title">Filters</h2>

                        <div class="mb-3">
                            <label for="category" class="form-label small mb-1">Category</label>
                            <select name="category" id="category" class="form-select form-select-sm">
                                <option value="">All categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->slug }}" @selected($selectedCategorySlug === $category->slug)>{!! str_repeat('&raquo; ', $category->depth) !!}{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="brand" class="form-label small mb-1">Brand</label>
                            <select name="brand" id="brand" class="form-select form-select-sm">
                                <option value="">All brands</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->slug }}" @selected($selectedBrandSlug === $brand->slug)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($selectedCategorySlug || $selectedBrandSlug)
                            <a href="{{ route('shop.products.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear filters</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const categorySelect = document.getElementById('category');
                const brandSelect = document.getElementById('brand');
                const baseUrl = @json(url('/products'));

                const navigate = () => {
                    const category = categorySelect.value;
                    const brand = brandSelect.value;

                    let path = baseUrl;

                    if (category && brand) {
                        path += `/category/${encodeURIComponent(category)}/brand/${encodeURIComponent(brand)}`;
                    } else if (category) {
                        path += `/category/${encodeURIComponent(category)}`;
                    } else if (brand) {
                        path += `/brand/${encodeURIComponent(brand)}`;
                    }

                    window.location.href = path;
                };

                categorySelect.addEventListener('change', navigate);
                brandSelect.addEventListener('change', navigate);
            })();
        </script>
    @endpush
@endsection
