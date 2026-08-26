@extends('layouts.admin')

@section('title', 'Media')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Media</h1>
        <a href="{{ route('admin.media.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>New Media
        </a>
    </div>

    <div class="btn-group mb-3" role="group" aria-label="Filter by purpose">
        <a href="{{ route('admin.media.index') }}" class="btn btn-sm {{ $purpose === null ? 'btn-primary' : 'btn-outline-primary' }}">
            All
        </a>
        @foreach (\App\Models\MediaAsset::PURPOSES as $option)
            <a href="{{ route('admin.media.index', ['purpose' => $option]) }}" class="btn btn-sm text-capitalize {{ $purpose === $option ? 'btn-primary' : 'btn-outline-primary' }}">
                {{ $option }}
            </a>
        @endforeach
    </div>

    @if ($assets->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                @if ($purpose)
                    No media items tagged "{{ $purpose }}" yet.
                @else
                    No media items yet.
                @endif
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach ($assets as $asset)
                <div class="col-6 col-sm-4 col-md-3 col-xl-2 js-delete-row">
                    <div class="card h-100">
                        <div class="ratio ratio-1x1 bg-light">
                            @if ($asset->getFirstMedia('file'))
                                <img src="{{ $asset->getFirstMediaUrl('file', 'thumb') }}" alt="{{ $asset->name ?? 'Media item' }}" class="card-img-top object-fit-cover">
                            @else
                                <div class="d-flex align-items-center justify-content-center text-muted">
                                    <i class="bi bi-image fs-1"></i>
                                </div>
                            @endif
                        </div>
                        <div class="card-body p-2">
                            <p class="small text-truncate mb-1" title="{{ $asset->name }}">{{ $asset->name ?: '(untitled)' }}</p>
                            <div class="mb-2">
                                @foreach ($asset->usable_for as $itemPurpose)
                                    <span class="badge text-bg-secondary text-capitalize">{{ $itemPurpose }}</span>
                                @endforeach
                            </div>
                            <x-row-actions
                                :edit-url="route('admin.media.edit', $asset)"
                                :delete-url="route('admin.media.destroy', $asset)"
                                :delete-label="$asset->name ?: 'this media item'"
                            />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $assets->links() }}
        </div>
    @endif
@endsection
