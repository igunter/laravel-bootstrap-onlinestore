@extends('layouts.admin')

@section('title', 'New Product')

@section('content')
    <h1 class="h4 mb-3">New Product</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                @include('admin.products._form')
            </form>
        </div>
    </div>
@endsection
