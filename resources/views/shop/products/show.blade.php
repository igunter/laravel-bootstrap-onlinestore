@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('shop.products.index') }}">Shop</a></li>
            @foreach ($categoryBreadcrumbs as $breadcrumbCategory)
                <li class="breadcrumb-item"><a href="{{ route('shop.categories.show', $breadcrumbCategory) }}">{{ $breadcrumbCategory->name }}</a></li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="ratio ratio-1x1 bg-light rounded">
                <img id="main-product-image" src="{{ $product->getFirstMediaUrl('images', 'large') }}" alt="{{ $product->name }}" class="w-100 h-100 object-fit-cover rounded @if (! $product->getFirstMedia('images')) d-none @endif">
                <div id="no-product-image" class="d-flex align-items-center justify-content-center text-muted @if ($product->getFirstMedia('images')) d-none @endif">
                    <i class="bi bi-image" style="font-size: 5rem;"></i>
                </div>
            </div>

            <div id="product-thumbnail-list" class="d-flex flex-wrap gap-2 mt-2 @if ($product->getMedia('images')->isEmpty()) d-none @endif">
                @foreach ($product->getMedia('images') as $media)
                    <img
                        src="{{ $media->getUrl('thumb') }}"
                        data-large-url="{{ $media->getUrl('large') }}"
                        alt="{{ $product->name }}"
                        class="img-thumbnail product-thumbnail @if ($loop->first) active @endif"
                        style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;"
                    >
                @endforeach
            </div>

            <script id="product-gallery-data" type="application/json">{!! json_encode($product->getMedia('images')->map(fn ($media) => ['thumb' => $media->getUrl('thumb'), 'large' => $media->getUrl('large')])->values()) !!}</script>

            @push('head')
                <style>
                    .product-thumbnail.active {
                        border-color: var(--bs-primary);
                        border-width: 2px;
                    }
                </style>
            @endpush

            @push('scripts')
                <script>
                    (function () {
                        // The product's own images, used whenever no variant is selected
                        // (or the selected variant — see the option-select script further
                        // down the page — has none of its own).
                        const defaultImages = JSON.parse(document.getElementById('product-gallery-data').textContent);

                        const mainImage = document.getElementById('main-product-image');
                        const noImagePlaceholder = document.getElementById('no-product-image');
                        const thumbnailList = document.getElementById('product-thumbnail-list');

                        const renderThumbnails = (images) => {
                            thumbnailList.innerHTML = '';

                            images.forEach((image, index) => {
                                const thumbnail = document.createElement('img');
                                thumbnail.src = image.thumb;
                                thumbnail.dataset.largeUrl = image.large;
                                thumbnail.alt = @json($product->name);
                                thumbnail.className = 'img-thumbnail product-thumbnail' + (index === 0 ? ' active' : '');
                                thumbnail.style.width = '80px';
                                thumbnail.style.height = '80px';
                                thumbnail.style.objectFit = 'cover';
                                thumbnail.style.cursor = 'pointer';

                                thumbnail.addEventListener('click', () => {
                                    mainImage.src = image.large;

                                    thumbnailList.querySelectorAll('.product-thumbnail').forEach((other) => other.classList.remove('active'));
                                    thumbnail.classList.add('active');
                                });

                                thumbnailList.appendChild(thumbnail);
                            });

                            thumbnailList.classList.toggle('d-none', images.length === 0);
                        };

                        // Exposed so the variant option-select script can swap the whole
                        // gallery to match the chosen variant's images. Pass an empty
                        // array (or nothing selected) to fall back to the product's own.
                        window.setProductGalleryImages = (images) => {
                            const gallery = images && images.length ? images : defaultImages;

                            mainImage.classList.toggle('d-none', gallery.length === 0);
                            noImagePlaceholder.classList.toggle('d-none', gallery.length > 0);

                            if (gallery.length) mainImage.src = gallery[0].large;

                            renderThumbnails(gallery);
                        };

                        renderThumbnails(defaultImages);
                    })();
                </script>
            @endpush
        </div>

        <div class="col-md-6">
            <h1 class="h3">{{ $product->name }}</h1>

            @if ($product->brand)
                <p class="text-muted">{{ $product->brand->name }}</p>
            @endif

            @if ($product->description)
                <p>{{ $product->description }}</p>
            @endif

            @if ($product->has_variants)
                @foreach ($product->options as $option)
                    <div class="mb-3">
                        <label class="form-label" for="option-{{ $option->id }}">{{ $option->name }}</label>
                        <select id="option-{{ $option->id }}" class="form-select variant-option-select" data-option-id="{{ $option->id }}">
                            <option value="">Choose {{ $option->name }}</option>
                            @foreach ($option->values as $value)
                                <option value="{{ $value->id }}">{{ $value->value }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div id="variant-info" class="border rounded p-3">
                    <p class="fs-4 mb-1" id="variant-price">Select options to see price</p>
                    <p class="mb-0 text-muted" id="variant-stock"></p>
                    <input type="hidden" id="selected-variant-id" value="">
                </div>

                <script id="variant-data" type="application/json">{!! json_encode($variants) !!}</script>

                @push('scripts')
                    <script>
                        (function () {
                            const variants = JSON.parse(document.getElementById('variant-data').textContent);
                            const selects = Array.from(document.querySelectorAll('.variant-option-select'));
                            const optionIds = selects.map((select) => Number(select.dataset.optionId));
                            const priceEl = document.getElementById('variant-price');
                            const stockEl = document.getElementById('variant-stock');
                            const hiddenInput = document.getElementById('selected-variant-id');

                            const getSelection = () => {
                                const selection = {};

                                selects.forEach((select) => {
                                    if (select.value) selection[select.dataset.optionId] = Number(select.value);
                                });

                                return selection;
                            };

                            const combinationExists = (selection) => variants.some((variant) => Object.entries(selection).every(
                                ([id, value]) => variant.values[id] === value
                            ));

                            // If the select the shopper just changed no longer pairs with what's
                            // selected elsewhere, clear those other selects rather than leaving an
                            // impossible combination in place.
                            const clearConflictingSiblings = (changedSelect) => {
                                if (!changedSelect.value) return;

                                const changedOptionId = changedSelect.dataset.optionId;
                                const changedValue = Number(changedSelect.value);

                                selects.forEach((other) => {
                                    if (other === changedSelect || !other.value) return;

                                    const pair = {
                                        [changedOptionId]: changedValue,
                                        [other.dataset.optionId]: Number(other.value),
                                    };

                                    if (!combinationExists(pair)) other.value = '';
                                });
                            };

                            // Every select is filtered by every OTHER select's current value, in
                            // both directions. Safety net: if that would hide every real value in
                            // a select (only possible with a deliberately incomplete variant
                            // matrix), leave that select's list untouched instead of trapping the
                            // shopper with no option left to click.
                            const refreshAvailability = () => {
                                const selection = getSelection();

                                selects.forEach((select) => {
                                    const optionId = select.dataset.optionId;
                                    const candidates = Array.from(select.options).filter((opt) => opt.value);
                                    const availability = candidates.map((opt) => combinationExists({ ...selection, [optionId]: Number(opt.value) }));
                                    const allHidden = availability.every((available) => !available);

                                    candidates.forEach((opt, index) => {
                                        const available = allHidden || availability[index];

                                        opt.hidden = !available;
                                        opt.disabled = !available;
                                    });
                                });
                            };

                            const updateDisplay = () => {
                                const selection = getSelection();
                                const variant = optionIds.every((id) => selection[id] !== undefined)
                                    ? variants.find((v) => optionIds.every((id) => v.values[id] === selection[id]))
                                    : null;

                                if (!variant) {
                                    priceEl.textContent = 'Select options to see price';
                                    stockEl.textContent = '';
                                    hiddenInput.value = '';
                                    window.setProductGalleryImages?.([]);

                                    return;
                                }

                                priceEl.textContent = `£${variant.price.toFixed(2)}`;
                                stockEl.textContent = variant.stock > 0 ? `${variant.stock} in stock` : 'Out of stock';
                                hiddenInput.value = variant.id;
                                window.setProductGalleryImages?.(variant.images || []);
                            };

                            selects.forEach((select) => select.addEventListener('change', () => {
                                clearConflictingSiblings(select);
                                refreshAvailability();
                                updateDisplay();
                            }));

                            refreshAvailability();
                            updateDisplay();
                        })();
                    </script>
                @endpush
            @else
                @php $variant = $product->standaloneVariant(); @endphp
                <p class="fs-4">£{{ number_format($product->base_price, 2) }}</p>
                @if ($variant)
                    <p class="text-muted">
                        @if (! $variant->is_active)
                            Unavailable
                        @elseif ($variant->stock_quantity > 0)
                            {{ $variant->stock_quantity }} in stock
                        @else
                            Out of stock
                        @endif
                    </p>
                @endif
            @endif
        </div>
    </div>
@endsection
