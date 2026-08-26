@props(['context', 'fileInputId' => null, 'fieldName' => 'media_asset_id', 'modalId' => 'mediaPicker', 'multiple' => false])

@php
    $items = \App\Models\MediaAsset::whereJsonContains('usable_for', $context)->latest()->get();
@endphp

<div class="mt-2">
    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
        <i class="bi bi-images me-1"></i>Choose from library
    </button>

    @if ($multiple)
        <div id="{{ $modalId }}-inputs"></div>
        <div id="{{ $modalId }}-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
    @else
        <input type="hidden" name="{{ $fieldName }}" id="{{ $modalId }}-input">
        <div id="{{ $modalId }}-preview" class="mt-2"></div>
    @endif
</div>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Choose from media library</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($items->isEmpty())
                    <p class="text-muted mb-0">
                        No media items tagged "{{ $context }}" yet.
                        <a href="{{ route('admin.media.create') }}" target="_blank">Add one</a>.
                    </p>
                @else
                    <div class="row g-2">
                        @foreach ($items as $item)
                            <div class="col-4 col-md-3">
                                <button
                                    type="button"
                                    class="btn p-1 border w-100 media-picker-item"
                                    data-id="{{ $item->id }}"
                                    data-thumb="{{ $item->getFirstMediaUrl('file', 'thumb') }}"
                                    data-name="{{ $item->name }}"
                                    @if (! $multiple) data-bs-dismiss="modal" @endif
                                >
                                    <span class="ratio ratio-1x1 d-block">
                                        <img src="{{ $item->getFirstMediaUrl('file', 'thumb') }}" class="w-100 h-100 object-fit-cover" alt="{{ $item->name }}">
                                    </span>
                                    <span class="d-block small text-truncate">{{ $item->name ?: '(untitled)' }}</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @if ($multiple && $items->isNotEmpty())
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="{{ $modalId }}-add" data-bs-dismiss="modal">Add selected</button>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    (function () {
        @if ($multiple)
            const selected = new Map();

            document.querySelectorAll('#{{ $modalId }} .media-picker-item').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    this.classList.toggle('border-primary');
                    this.classList.toggle('bg-primary-subtle');

                    if (selected.has(this.dataset.id)) {
                        selected.delete(this.dataset.id);
                    } else {
                        selected.set(this.dataset.id, this.dataset.thumb);
                    }
                });
            });

            document.getElementById('{{ $modalId }}-add')?.addEventListener('click', function () {
                const inputsContainer = document.getElementById('{{ $modalId }}-inputs');
                const previewContainer = document.getElementById('{{ $modalId }}-preview');

                selected.forEach(function (thumb, id) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = '{{ $fieldName }}[]';
                    input.value = id;
                    inputsContainer.appendChild(input);

                    const wrapper = document.createElement('div');
                    wrapper.className = 'position-relative';
                    wrapper.innerHTML = '<img src="' + thumb + '" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">'
                        + '<button type="button" class="btn btn-sm btn-danger py-0 px-1 position-absolute top-0 end-0 remove-staged-pick">'
                        + '<i class="bi bi-x"></i></button>';

                    wrapper.querySelector('.remove-staged-pick').addEventListener('click', function () {
                        input.remove();
                        wrapper.remove();
                    });

                    previewContainer.appendChild(wrapper);
                });

                selected.forEach(function (_thumb, id) {
                    const btn = document.querySelector('#{{ $modalId }} .media-picker-item[data-id="' + id + '"]');
                    btn?.classList.remove('border-primary', 'bg-primary-subtle');
                });
                selected.clear();
            });
        @else
            document.querySelectorAll('#{{ $modalId }} .media-picker-item').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('{{ $modalId }}-input').value = this.dataset.id;
                    document.getElementById('{{ $modalId }}-preview').innerHTML =
                        '<img src="' + this.dataset.thumb + '" class="img-thumbnail" style="max-width: 150px;">';

                    @if ($fileInputId)
                        var fileInput = document.getElementById('{{ $fileInputId }}');
                        if (fileInput) fileInput.value = '';
                    @endif
                });
            });
        @endif
    })();
</script>
