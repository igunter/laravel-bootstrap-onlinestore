@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-3">
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
@endsection
