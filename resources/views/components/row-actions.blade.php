@props(['editUrl', 'deleteUrl', 'deleteLabel' => 'this item', 'addUrl' => null, 'addLabel' => 'Add'])

<div class="btn-group btn-group-sm" role="group">
    @if ($addUrl)
        <a href="{{ $addUrl }}" class="btn btn-outline-secondary" title="{{ $addLabel }}">
            <i class="bi bi-plus-lg"></i>
        </a>
    @endif
    <a href="{{ $editUrl }}" class="btn btn-outline-primary">
        <i class="bi bi-pencil"></i><span class="d-none d-md-inline ms-1">Edit</span>
    </a>
    <button
        type="button"
        class="btn btn-outline-danger js-delete-trigger"
        data-delete-url="{{ $deleteUrl }}"
        data-confirm="Delete {{ $deleteLabel }}?"
    >
        <i class="bi bi-trash"></i><span class="d-none d-md-inline ms-1">Delete</span>
    </button>
</div>
