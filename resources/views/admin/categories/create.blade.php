@extends('layouts.admin')

@section('title', 'New Category')

@section('content')
    <h1 class="h4 mb-3">New Category</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data">
                @include('admin.categories._form')
            </form>
        </div>
    </div>
@endsection
