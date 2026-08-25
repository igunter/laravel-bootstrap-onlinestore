@foreach ($categories as $category)
    <tr class="category-row" draggable="true" data-id="{{ $category->id }}" data-depth="{{ $depth }}">
        <td>
            <span style="padding-left: {{ $depth * 1.5 }}rem;" class="d-inline-flex align-items-center">
                <i class="bi bi-grip-vertical text-muted category-drag-handle me-1" style="cursor: grab;"></i>
                {{ $category->name }}
            </span>
        </td>
        <td class="d-none d-md-table-cell">{{ $category->slug }}</td>
        <td>
            @if ($category->is_active)
                <span class="badge text-bg-success">Active</span>
            @else
                <span class="badge text-bg-secondary">Inactive</span>
            @endif
        </td>
        <td class="text-end">
            <x-row-actions
                :add-url="route('admin.categories.create', ['parent_id' => $category->id])"
                add-label="Add subcategory"
                :edit-url="route('admin.categories.edit', $category)"
                :delete-url="route('admin.categories.destroy', $category)"
                :delete-label="$category->name"
            />
        </td>
    </tr>
    @if ($category->children->isNotEmpty())
        @include('admin.categories._rows', ['categories' => $category->children, 'depth' => $depth + 1])
    @endif
@endforeach
