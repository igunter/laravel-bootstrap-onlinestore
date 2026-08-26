@csrf

@php
    $hasVariants = old('has_variants', isset($product) ? ($product->has_variants ? '1' : '0') : '0');
    $standaloneVariant = isset($product) ? $product->standaloneVariant() : null;
@endphp

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name ?? '') }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label d-block">Product type</label>
    <div class="form-check form-check-inline">
        <input type="radio" name="has_variants" id="type_standalone" value="0" class="form-check-input product-type-radio" @checked($hasVariants == '0')>
        <label for="type_standalone" class="form-check-label">Standalone product</label>
    </div>
    <div class="form-check form-check-inline">
        <input type="radio" name="has_variants" id="type_variants" value="1" class="form-check-input product-type-radio" @checked($hasVariants == '1')>
        <label for="type_variants" class="form-check-label">Has variants (e.g. Size, Color)</label>
    </div>
    @error('has_variants')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="category_id" class="form-label">Category</label>
        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
            <option value="">— None —</option>
            @foreach ($categoryOptions as $id => $label)
                <option value="{{ $id }}" @selected(old('category_id', $product->category_id ?? '') == $id)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="brand_id" class="form-label">Brand</label>
        <select name="brand_id" id="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
            <option value="">— None —</option>
            @foreach ($brandOptions as $id => $label)
                <option value="{{ $id }}" @selected(old('brand_id', $product->brand_id ?? '') == $id)>{{ $label }}</option>
            @endforeach
        </select>
        @error('brand_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $product->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-6 col-lg-3 mb-3 order-1">
        <label for="base_price" class="form-label">Base price</label>
        <div class="input-group">
            <span class="input-group-text">£</span>
            <input type="number" step="0.01" min="0" name="base_price" id="base_price" class="form-control @error('base_price') is-invalid @enderror" value="{{ old('base_price', $product->base_price ?? '') }}" required>
        </div>
        @error('base_price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-6 col-lg-3 mb-3 order-2 standalone-only-field" id="stock-field">
        <label for="stock_quantity" class="form-label">Stock quantity</label>
        <div class="input-group">
            <span class="input-group-text">x</span>
            <input type="number" min="0" name="stock_quantity" id="stock_quantity" class="form-control @error('stock_quantity') is-invalid @enderror" value="{{ old('stock_quantity', $standaloneVariant->stock_quantity ?? 0) }}">
        </div>
        @error('stock_quantity')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-6 col-lg-3 mb-3 order-3 standalone-only-field" id="sku-field">
        <label for="sku" class="form-label">SKU</label>
        <input type="text" name="sku" id="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $standaloneVariant->sku ?? '') }}">
        @error('sku')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-6 col-lg-3 mb-3 order-4 standalone-only-field" id="upc-field">
        <label for="upc" class="form-label">UPC</label>
        <input type="text" name="upc" id="upc" class="form-control @error('upc') is-invalid @enderror" value="{{ old('upc', $standaloneVariant->upc ?? '') }}">
        @error('upc')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div id="variants-fields" class="row">
    <div class="col-md-9 col-lg-6 mb-3">
        <label for="base_sku" class="form-label">Base SKU</label>
        <input type="text" name="base_sku" id="base_sku" class="form-control @error('base_sku') is-invalid @enderror" value="{{ old('base_sku', $product->base_sku ?? '') }}">
        @error('base_sku')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Each generated variant's SKU is built from this (e.g. "{{ old('base_sku', $product->base_sku ?? 'HOODIE') }}-S-RED").</div>
    </div>
</div>

<div class="mb-3">
    <label for="images" class="form-label">Images</label>
    <input type="file" name="images[]" id="images" class="form-control @error('images.*') is-invalid @enderror" accept="image/*" multiple>
    @error('images.*')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    @if (isset($product) && $product->getMedia('images')->isNotEmpty())
        <div class="d-flex flex-wrap gap-2 mt-2" id="product-image-list" data-reorder-url="{{ route('admin.products.images.reorder', $product) }}">
            @foreach ($product->getMedia('images') as $media)
                <div class="position-relative" draggable="true" data-media-id="{{ $media->id }}" style="cursor: grab;">
                    <img src="{{ $media->getUrl('thumb') }}" alt="{{ $product->name }}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                    <form action="{{ route('admin.products.images.destroy', [$product, $media]) }}" method="POST" class="position-absolute top-0 end-0" onsubmit="return confirm('Remove this image?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger py-0 px-1" title="Remove image">
                            <i class="bi bi-x"></i>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
        <div class="form-text">Drag images to reorder.</div>
    @endif

    <x-media-picker context="product" field-name="media_asset_ids" modal-id="productMediaPicker" multiple />
</div>

<div class="form-check mb-3">
    <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" @checked(old('is_active', $product->is_active ?? true))>
    <label for="is_active" class="form-check-label">Active</label>
</div>

<button type="submit" class="btn btn-primary">Save</button>
<a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>

@push('scripts')
    <script>
        (function () {
            const radios = document.querySelectorAll('.product-type-radio');
            const standaloneFields = document.querySelectorAll('.standalone-only-field');
            const variantsFields = document.getElementById('variants-fields');

            if (!radios.length || !standaloneFields.length || !variantsFields) return;

            const sync = () => {
                const isStandalone = document.querySelector('.product-type-radio[value="0"]').checked;
                standaloneFields.forEach((field) => { field.style.display = isStandalone ? '' : 'none'; });
                variantsFields.style.display = isStandalone ? 'none' : '';
            };

            radios.forEach((radio) => radio.addEventListener('change', sync));
            sync();
        })();
    </script>
@endpush
