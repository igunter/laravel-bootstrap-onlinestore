@extends('layouts.admin')

@section('title', 'Edit Brand')

@section('content')
    <h1 class="h4 mb-3">Edit Brand</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.brands.update', $brand) }}" method="POST" enctype="multipart/form-data">
                @method('PUT')
                @include('admin.brands._form')
            </form>
        </div>
    </div>
@endsection
