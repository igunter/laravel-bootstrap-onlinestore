@props(['context', 'fileInputId', 'fieldName' => 'media_asset_id', 'modalId' => 'mediaPicker'])

@php
    $items = \App\Models\MediaAsset::whereJsonContains('usable_for', $context)->latest()->get();
@endphp

<div class="mt-2">
    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
        <i class="bi bi-images me-1"></i>Choose from library
    </button>
    <input type="hidden" name="{{ $fieldName }}" id="{{ $modalId }}-input">
    <div id="{{ $modalId }}-preview" class="mt-2"></div>
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
                                    data-bs-dismiss="modal"
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
        </div>
    </div>
</div>

<script>
    (function () {
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
    })();
</script>
