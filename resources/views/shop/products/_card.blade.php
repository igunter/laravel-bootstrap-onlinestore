<div class="col-6 col-md-4 col-lg-3">
    <a href="{{ route('shop.products.show', $product) }}" class="text-decoration-none text-body">
        <div class="card h-100">
            <div class="ratio ratio-1x1 bg-light">
                @if ($product->getFirstMedia('images'))
                    <img src="{{ $product->getFirstMediaUrl('images', 'thumb') }}" alt="{{ $product->name }}" class="card-img-top object-fit-cover">
                @else
                    <div class="d-flex align-items-center justify-content-center text-muted">
                        <i class="bi bi-image fs-1"></i>
                    </div>
                @endif
            </div>
            <div class="card-body">
                <p class="text-truncate mb-1">{{ $product->name }}</p>
                @if ($product->brand)
                    <p class="small text-muted mb-1">{{ $product->brand->name }}</p>
                @endif
                <p class="fw-semibold mb-0">£{{ number_format($product->base_price, 2) }}</p>
            </div>
        </div>
    </a>
</div>
