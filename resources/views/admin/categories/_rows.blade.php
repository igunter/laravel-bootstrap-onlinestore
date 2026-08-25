@foreach ($categories as $category)
    <tr class="category-row" draggable="true" data-id="{{ $category->id }}" data-depth="{{ $depth }}">
        <td>
            <i class="bi bi-grip-vertical text-muted category-drag-handle" style="cursor: grab;"></i>
            {{ str_repeat('— ', $depth) }}{{ $category->name }}
        </td>
        <td>{{ $category->slug }}</td>
        <td>
            @if ($category->is_active)
                <span class="badge text-bg-success">Active</span>
            @else
                <span class="badge text-bg-secondary">Inactive</span>
            @endif
        </td>
        <td class="text-end">
            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </td>
    </tr>
    @if ($category->children->isNotEmpty())
        @include('admin.categories._rows', ['categories' => $category->children, 'depth' => $depth + 1])
    @endif
@endforeach
