@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Categories</h1>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>New Category
        </a>
    </div>

    <p class="text-muted small">Drag the <i class="bi bi-grip-vertical"></i> handle to reorder. Drop near the top/bottom edge of a row to place it before/after; drop on the middle of a row to nest it inside.</p>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="categories-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        @include('admin.categories._rows', ['categories' => [$category], 'depth' => 0])
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No categories yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .category-row.drop-before { box-shadow: inset 0 2px 0 0 var(--bs-primary); }
        .category-row.drop-after { box-shadow: inset 0 -2px 0 0 var(--bs-primary); }
        .category-row.drop-into { background-color: var(--bs-primary-bg-subtle, rgba(13, 110, 253, .15)); }
    </style>
    <script>
        (function () {
            const tbody = document.querySelector('#categories-table tbody');

            if (!tbody) return;

            let draggedRow = null;
            let draggedBlockIds = null;
            let currentTarget = null;
            let currentZone = null;

            const clearIndicator = () => {
                if (currentTarget) {
                    currentTarget.classList.remove('drop-before', 'drop-after', 'drop-into');
                }
                currentTarget = null;
                currentZone = null;
            };

            tbody.addEventListener('dragstart', (event) => {
                const row = event.target.closest('.category-row');

                if (!row) return;

                draggedRow = row;
                event.dataTransfer.effectAllowed = 'move';

                const depth = Number(row.dataset.depth);
                draggedBlockIds = new Set([Number(row.dataset.id)]);

                let sibling = row.nextElementSibling;
                while (sibling && Number(sibling.dataset.depth) > depth) {
                    draggedBlockIds.add(Number(sibling.dataset.id));
                    sibling = sibling.nextElementSibling;
                }
            });

            tbody.addEventListener('dragover', (event) => {
                const targetRow = event.target.closest('.category-row');

                if (!draggedRow || !targetRow || draggedBlockIds.has(Number(targetRow.dataset.id))) {
                    clearIndicator();
                    return;
                }

                event.preventDefault();

                const rect = targetRow.getBoundingClientRect();
                const offset = (event.clientY - rect.top) / rect.height;
                const zone = offset < 0.25 ? 'before' : offset > 0.75 ? 'after' : 'into';

                if (targetRow !== currentTarget || zone !== currentZone) {
                    clearIndicator();
                    currentTarget = targetRow;
                    currentZone = zone;
                    targetRow.classList.add(`drop-${zone}`);
                }
            });

            tbody.addEventListener('dragleave', (event) => {
                if (event.target.closest('.category-row') === currentTarget && !tbody.contains(event.relatedTarget)) {
                    clearIndicator();
                }
            });

            tbody.addEventListener('drop', (event) => {
                event.preventDefault();

                if (!draggedRow || !currentTarget || !currentZone) return;

                const categoryId = draggedRow.dataset.id;
                const targetId = currentTarget.dataset.id;
                const position = currentZone;

                clearIndicator();

                const moveUrl = '{{ route('admin.categories.move', ['category' => '__ID__']) }}'.replace('__ID__', categoryId);

                fetch(moveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        target_id: targetId,
                        position: position,
                    }),
                }).then(() => {
                    window.location.reload();
                });
            });

            tbody.addEventListener('dragend', () => {
                draggedRow = null;
                draggedBlockIds = null;
                clearIndicator();
            });
        })();
    </script>
@endpush
