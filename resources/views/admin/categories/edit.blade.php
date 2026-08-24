@extends('layouts.admin')

@section('title', 'Edit Category')

@section('content')
    <h1 class="h4 mb-3">Edit Category</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
                @method('PUT')
                @include('admin.categories._form')
            </form>
        </div>
    </div>
@endsection
