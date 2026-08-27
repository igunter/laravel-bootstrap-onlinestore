@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Products</h6>
                    <p class="h3 mb-0">{{ $productCount }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Categories</h6>
                    <p class="h3 mb-0">{{ $categoryCount }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Brands</h6>
                    <p class="h3 mb-0">{{ $brandCount }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-danger mt-4">
        <div class="card-header bg-danger-subtle text-danger-emphasis">Danger zone</div>
        <div class="card-body">
            <h6>Reset demo data</h6>
            <p class="text-muted mb-3">
                Wipes the entire database and reseeds it from scratch — every product, order, brand, category,
                media item, and non-admin user is permanently deleted and replaced with fresh demo data. This is a
                demo/test project, so this is safe to use here, but it cannot be undone.
            </p>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetDemoDataModal">
                <i class="bi bi-arrow-repeat me-1"></i>Reset demo data
            </button>
        </div>
    </div>
@endsection
