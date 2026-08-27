@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
    <h1 class="h4 mb-3">Edit Product</h1>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
                @method('PUT')
                @include('admin.products._form')
            </form>
        </div>
    </div>

    {{-- One standalone delete form per product image, kept outside the main product
         form above (nesting a <form> inside another is invalid HTML) — each image's
         delete button in _form.blade.php targets one of these via its `form` attribute. --}}
    @foreach ($product->getMedia('images') as $media)
        <form id="delete-product-image-{{ $media->id }}" action="{{ route('admin.products.images.destroy', [$product, $media]) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    @if ($product->has_variants)
    <style>
        .option-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
        }
        .option-rename-form,
        .option-add-value-form {
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .option-add-value-form {
            flex: 1 1 auto;
            max-width: 22rem;
        }
        .option-add-value-form input {
            flex: 1 1 auto;
            min-width: 0;
        }

        @media (max-width: 575.98px) {
            .option-header {
                display: grid;
                grid-template-columns: 1fr auto;
                column-gap: .5rem;
                row-gap: .5rem;
            }
            .option-rename-form { grid-column: 1; grid-row: 1; }
            .option-delete-form { grid-column: 2; grid-row: 1; justify-self: end; }
            .option-add-value-form { display: contents; }
            .option-add-value-form input { grid-column: 1; grid-row: 2; width: 100%; }
            .option-add-value-form button { grid-column: 2; grid-row: 2; }
        }
    </style>
    <div class="card mb-4">
        <div class="card-header">Options</div>
        <div class="card-body">
            <div id="option-value-feedback"></div>

            @forelse ($product->options as $option)
                <div class="border rounded p-3 mb-3">
                    <div class="option-header mb-2">
                        <form action="{{ route('admin.products.options.update', [$product, $option]) }}" method="POST" class="option-rename-form">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $option->name }}" class="form-control form-control-sm" style="width: auto; max-width: 8rem;">
                            <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">Rename</button>
                        </form>

                        <form id="add-value-form-{{ $option->id }}" data-option-id="{{ $option->id }}" action="{{ route('admin.products.options.values.store', [$product, $option]) }}" method="POST" class="option-add-value-form">
                            @csrf
                            <input type="text" name="value" class="form-control form-control-sm" placeholder="New value(s), comma-separated" required>
                            <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Add</button>
                        </form>

                        <form action="{{ route('admin.products.options.destroy', [$product, $option]) }}" method="POST" class="option-delete-form" onsubmit="return confirm('Delete this option and all its values?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center" id="option-values-{{ $option->id }}">
                        @foreach ($option->values as $value)
                            <span class="badge text-bg-light border d-inline-flex align-items-center gap-1" id="value-badge-{{ $value->id }}">
                                {{ $value->value }}
                                <form id="delete-value-form-{{ $value->id }}" action="{{ route('admin.products.options.values.destroy', [$product, $option, $value]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this value?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" style="line-height: 1;" title="Remove value">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </form>
                            </span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-muted">No options yet. Add one below (e.g. "Size" with values "S, M, L").</p>
            @endforelse

            <hr>

            <form action="{{ route('admin.products.options.store', $product) }}" method="POST" id="add-option-form">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Option name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Size" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Values <span class="text-muted">(comma-separated)</span></label>
                        <input type="text" name="values" class="form-control" placeholder="e.g. S, M, L, XL" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Add option</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Variants</span>
            <form action="{{ route('admin.products.variants.generate', $product) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-magic me-1"></i>Generate variants
                </button>
            </form>
        </div>
        <div class="card-body">
            @if ($product->variants->isEmpty())
                <p class="text-muted mb-0">No variants yet. Click "Generate variants" to create them from the options above (or a single default variant if there are none).</p>
            @else
                <div id="variant-feedback"></div>

                <div class="row g-2 d-none d-lg-flex text-muted small mx-0 mb-2 pb-1">
                    <div class="col-2">Options</div>
                    <div class="col-2">SKU</div>
                    <div class="col-2">UPC</div>
                    <div class="col-2">Price</div>
                    <div class="col-1">Stock</div>
                    <div class="col-1">Active</div>
                    <div class="col-2"></div>
                </div>

                @foreach ($product->variants as $variant)
                    @php $rowFormId = 'variant-form-'.$variant->id; @endphp
                    <div class="row g-2 align-items-center border-top py-3 mx-0" data-variant-row="{{ $variant->id }}">
                        <div class="col-12 col-lg-2 text-muted small">{{ $variant->optionsLabel() ?: '—' }}</div>

                        <div class="col-6 col-lg-2">
                            <label class="form-label small mb-0 d-lg-none">SKU</label>
                            <input type="text" name="sku" form="{{ $rowFormId }}" value="{{ $variant->sku }}" class="form-control form-control-sm">
                        </div>

                        <div class="col-6 col-lg-2">
                            <label class="form-label small mb-0 d-lg-none">UPC</label>
                            <input type="text" name="upc" form="{{ $rowFormId }}" value="{{ $variant->upc }}" class="form-control form-control-sm">
                        </div>

                        <div class="col-6 col-lg-2">
                            <label class="form-label small mb-0 d-lg-none">Price</label>
                            <input type="number" step="0.01" min="0" name="price" form="{{ $rowFormId }}" value="{{ $variant->price }}" class="form-control form-control-sm" placeholder="£{{ number_format($product->base_price, 2) }}">
                        </div>

                        <div class="col-6 col-lg-1">
                            <label class="form-label small mb-0 d-lg-none">Stock</label>
                            <input type="number" min="0" name="stock_quantity" form="{{ $rowFormId }}" value="{{ $variant->stock_quantity }}" class="form-control form-control-sm">
                        </div>

                        <div class="col-6 col-lg-1">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" form="{{ $rowFormId }}" value="1" class="form-check-input" @checked($variant->is_active)>
                                <label class="form-check-label small d-lg-none">Active</label>
                            </div>
                        </div>

                        <div class="col-6 col-lg-2 text-lg-end">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#variant-images-{{ $variant->id }}" title="Images">
                                    <i class="bi bi-image"></i> <span class="variant-image-count">{{ $variant->getMedia('images')->count() }}</span>
                                </button>
                                <button type="submit" form="{{ $rowFormId }}" class="btn btn-outline-secondary variant-save-btn">Save</button>
                                <button type="submit" form="delete-{{ $rowFormId }}" class="btn btn-outline-danger variant-delete-btn" onclick="return confirm('Delete this variant?');">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12 collapse" id="variant-images-{{ $variant->id }}">
                            <div class="bg-light rounded p-2 mt-1">
                                <div class="d-flex flex-wrap gap-2 mb-2 variant-image-list" id="variant-image-list-{{ $variant->id }}" data-reorder-url="{{ route('admin.products.variants.images.reorder', [$product, $variant]) }}">
                                    @foreach ($variant->getMedia('images') as $media)
                                        <div class="position-relative" draggable="true" data-media-id="{{ $media->id }}" style="cursor: grab;">
                                            <img src="{{ $media->getUrl('thumb') }}" alt="{{ $variant->sku }}" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                                            <form action="{{ route('admin.products.variants.images.destroy', [$product, $variant, $media]) }}" method="POST" class="variant-image-delete-form position-absolute top-0 end-0" onsubmit="return confirm('Remove this image?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger py-0 px-1" title="Remove image">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>

                                <form action="{{ route('admin.products.variants.images.store', [$product, $variant]) }}" method="POST" enctype="multipart/form-data" class="variant-image-upload-form d-flex flex-wrap align-items-center gap-2" data-variant-id="{{ $variant->id }}">
                                    @csrf
                                    <input type="file" name="images[]" multiple accept="image/*" class="form-control form-control-sm" style="max-width: 260px;">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Upload</button>
                                    <x-media-picker context="product" field-name="media_asset_ids" :modal-id="'variantMediaPicker'.$variant->id" multiple />
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach

                @foreach ($product->variants as $variant)
                    <form id="variant-form-{{ $variant->id }}" action="{{ route('admin.products.variants.update', [$product, $variant]) }}" method="POST" class="d-none">
                        @csrf
                        @method('PUT')
                    </form>
                    <form id="delete-variant-form-{{ $variant->id }}" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            const feedback = document.getElementById('option-value-feedback');

            if (!feedback) return;

            const showFeedback = (message, isSuccess) => {
                feedback.innerHTML = `
                    <div class="alert alert-${isSuccess ? 'success' : 'danger'} alert-dismissible fade show py-2" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;

                setTimeout(() => {
                    feedback.querySelector('.alert')?.remove();
                }, 4000);
            };

            const wireDeleteForm = (form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const badge = form.closest('.badge');
                    const button = form.querySelector('button');
                    if (button) button.disabled = true;

                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new FormData(form),
                    })
                        .then(async (response) => {
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                showFeedback(data.message || 'Something went wrong.', false);
                                if (button) button.disabled = false;

                                return;
                            }

                            showFeedback(data.message || 'Value deleted.', true);
                            badge?.remove();
                        })
                        .catch(() => {
                            showFeedback('Network error — value was not deleted.', false);
                            if (button) button.disabled = false;
                        });
                });
            };

            document.querySelectorAll('form[id^="delete-value-form-"]').forEach(wireDeleteForm);

            document.querySelectorAll('form[id^="add-value-form-"]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const optionId = form.dataset.optionId;
                    const container = document.getElementById(`option-values-${optionId}`);
                    const input = form.querySelector('input[name="value"]');
                    const button = form.querySelector('button');

                    if (button) button.disabled = true;

                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new FormData(form),
                    })
                        .then(async (response) => {
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                showFeedback(data.message || 'Something went wrong.', false);

                                return;
                            }

                            (data.values || []).forEach((value) => {
                                const badge = document.createElement('span');
                                badge.className = 'badge text-bg-light border d-inline-flex align-items-center gap-1';
                                badge.id = `value-badge-${value.id}`;
                                badge.innerHTML = `
                                    ${value.value}
                                    <form id="delete-value-form-${value.id}" method="POST" class="d-inline">
                                        <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]')?.content ?? ''}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" style="line-height: 1;" title="Remove value">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </form>
                                `;
                                const newForm = badge.querySelector('form');
                                newForm.action = `{{ url('admin/products/'.$product->id.'/options') }}/${optionId}/values/${value.id}`;
                                wireDeleteForm(newForm);

                                container?.appendChild(badge);
                            });

                            showFeedback(data.message || 'Value(s) added.', true);
                            if (input) input.value = '';
                        })
                        .catch(() => showFeedback('Network error — value was not added.', false))
                        .finally(() => {
                            if (button) button.disabled = false;
                        });
                });
            });
        })();

        (function () {
            const feedback = document.getElementById('variant-feedback');

            if (!feedback) return;

            const showFeedback = (variant, isSuccess) => {
                feedback.innerHTML = `
                    <div class="alert alert-${isSuccess ? 'success' : 'danger'} alert-dismissible fade show py-2" role="alert">
                        ${variant}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;

                setTimeout(() => {
                    feedback.querySelector('.alert')?.remove();
                }, 4000);
            };

            document.querySelectorAll('form[id^="variant-form-"]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const variantId = form.id.replace('variant-form-', '');
                    const row = document.querySelector(`[data-variant-row="${variantId}"]`);
                    const saveButton = row?.querySelector('.variant-save-btn');
                    const originalLabel = saveButton?.textContent;

                    if (saveButton) {
                        saveButton.disabled = true;
                        saveButton.textContent = 'Saving…';
                    }

                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new FormData(form),
                    })
                        .then(async (response) => {
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                const message = data.errors
                                    ? Object.values(data.errors).flat().join(' ')
                                    : (data.message || 'Something went wrong.');

                                showFeedback(message, false);
                                row?.classList.add('bg-danger-subtle');
                                setTimeout(() => row?.classList.remove('bg-danger-subtle'), 1500);

                                return;
                            }

                            showFeedback(data.message || 'Variant updated.', true);
                            row?.classList.add('bg-success-subtle');
                            setTimeout(() => row?.classList.remove('bg-success-subtle'), 1500);
                        })
                        .catch(() => showFeedback('Network error — variant was not saved.', false))
                        .finally(() => {
                            if (saveButton) {
                                saveButton.disabled = false;
                                saveButton.textContent = originalLabel;
                            }
                        });
                });
            });

            document.querySelectorAll('form[id^="delete-variant-form-"]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const variantId = form.id.replace('delete-variant-form-', '');
                    const row = document.querySelector(`[data-variant-row="${variantId}"]`);
                    const deleteButton = row?.querySelector('.variant-delete-btn');

                    if (deleteButton) deleteButton.disabled = true;

                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new FormData(form),
                    })
                        .then(async (response) => {
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                showFeedback(data.message || 'Something went wrong.', false);
                                if (deleteButton) deleteButton.disabled = false;

                                return;
                            }

                            showFeedback(data.message || 'Variant deleted.', true);
                            row?.classList.add('bg-danger-subtle');
                            row?.addEventListener('transitionend', () => row.remove(), { once: true });
                            row?.style.setProperty('transition', 'opacity 0.3s');
                            requestAnimationFrame(() => row?.style.setProperty('opacity', '0'));
                            form.remove();
                            document.getElementById(`variant-form-${variantId}`)?.remove();
                        })
                        .catch(() => {
                            showFeedback('Network error — variant was not deleted.', false);
                            if (deleteButton) deleteButton.disabled = false;
                        });
                });
            });
        })();

        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            const showFeedback = (message, isSuccess) => {
                const feedback = document.getElementById('variant-feedback');
                if (!feedback) return;

                feedback.innerHTML = `
                    <div class="alert alert-${isSuccess ? 'success' : 'danger'} alert-dismissible fade show py-2" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;

                setTimeout(() => {
                    feedback.querySelector('.alert')?.remove();
                }, 4000);
            };

            const wireImageDeleteForm = (form, variantId) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const button = form.querySelector('button');
                    if (button) button.disabled = true;

                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new FormData(form),
                    })
                        .then(async (response) => {
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                showFeedback(data.message || 'Something went wrong.', false);
                                if (button) button.disabled = false;

                                return;
                            }

                            renderVariantImages(variantId, data.images || []);
                            showFeedback(data.message || 'Variant image removed.', true);
                        })
                        .catch(() => {
                            showFeedback('Network error — image was not removed.', false);
                            if (button) button.disabled = false;
                        });
                });
            };

            const makeSortableImageList = (container) => {
                if (!container) return;

                if (container.dataset.sortableBound !== '1') {
                    container.dataset.sortableBound = '1';

                    container.addEventListener('dragover', (event) => {
                        event.preventDefault();

                        const target = event.target.closest('[data-media-id]');
                        const dragging = container.querySelector('.dragging');

                        if (!dragging || !target || target === dragging) return;

                        const rect = target.getBoundingClientRect();
                        const isAfter = (event.clientX - rect.left) > rect.width / 2;
                        container.insertBefore(dragging, isAfter ? target.nextSibling : target);
                    });

                    container.addEventListener('drop', (event) => {
                        event.preventDefault();

                        const url = container.dataset.reorderUrl;
                        if (!url) return;

                        const order = Array.from(container.querySelectorAll('[data-media-id]')).map((el) => el.dataset.mediaId);

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ order }),
                        })
                            .then((response) => {
                                if (!response.ok) showFeedback('Something went wrong reordering images.', false);
                            })
                            .catch(() => showFeedback('Network error — order was not saved.', false));
                    });
                }

                container.querySelectorAll('[data-media-id]').forEach((item) => {
                    if (item.dataset.dragBound === '1') return;
                    item.dataset.dragBound = '1';

                    item.addEventListener('dragstart', () => {
                        item.classList.add('dragging');
                        item.style.opacity = '0.4';
                    });
                    item.addEventListener('dragend', () => {
                        item.classList.remove('dragging');
                        item.style.opacity = '';
                    });
                });
            };

            const renderVariantImages = (variantId, images) => {
                const list = document.getElementById(`variant-image-list-${variantId}`);
                if (!list) return;

                list.innerHTML = '';

                images.forEach((image) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'position-relative';
                    wrapper.setAttribute('draggable', 'true');
                    wrapper.style.cursor = 'grab';
                    wrapper.dataset.mediaId = image.id;
                    wrapper.innerHTML = `
                        <img src="${image.thumb_url}" alt="" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                        <form method="POST" class="variant-image-delete-form position-absolute top-0 end-0">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-sm btn-danger py-0 px-1" title="Remove image">
                                <i class="bi bi-x"></i>
                            </button>
                        </form>
                    `;

                    const deleteForm = wrapper.querySelector('form');
                    deleteForm.action = `{{ url('admin/products/'.$product->id.'/variants') }}/${variantId}/images/${image.id}`;
                    wireImageDeleteForm(deleteForm, variantId);

                    list.appendChild(wrapper);
                });

                makeSortableImageList(list);

                const countLabel = document.querySelector(`[data-bs-target="#variant-images-${variantId}"] .variant-image-count`);
                if (countLabel) countLabel.textContent = images.length;
            };

            document.querySelectorAll('.variant-image-delete-form').forEach((form) => {
                const variantId = form.closest('[id^="variant-image-list-"]')?.id.replace('variant-image-list-', '');
                if (variantId) wireImageDeleteForm(form, variantId);
            });

            document.querySelectorAll('.variant-image-list').forEach(makeSortableImageList);
            makeSortableImageList(document.getElementById('product-image-list'));

            document.querySelectorAll('.variant-image-upload-form').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const variantId = form.dataset.variantId;
                    const uploadButton = form.querySelector('button[type="submit"]');
                    const fileInput = form.querySelector('input[type="file"]');

                    if (uploadButton) uploadButton.disabled = true;

                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new FormData(form),
                    })
                        .then(async (response) => {
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                const message = data.errors
                                    ? Object.values(data.errors).flat().join(' ')
                                    : (data.message || 'Something went wrong.');

                                showFeedback(message, false);

                                return;
                            }

                            renderVariantImages(variantId, data.images || []);
                            showFeedback(data.message || 'Variant image(s) added.', true);

                            if (fileInput) fileInput.value = '';
                            document.getElementById(`variantMediaPicker${variantId}-inputs`)?.replaceChildren();
                            document.getElementById(`variantMediaPicker${variantId}-preview`)?.replaceChildren();
                        })
                        .catch(() => showFeedback('Network error — image(s) were not added.', false))
                        .finally(() => {
                            if (uploadButton) uploadButton.disabled = false;
                        });
                });

                // Picking from the library only stages the pick (so it can be
                // removed before committing); submit right away instead of
                // requiring a separate manual "Upload" click.
                const variantId = form.dataset.variantId;
                document.getElementById(`variantMediaPicker${variantId}-add`)?.addEventListener('click', () => {
                    setTimeout(() => form.requestSubmit(), 0);
                });
            });
        })();
    </script>
@endpush
