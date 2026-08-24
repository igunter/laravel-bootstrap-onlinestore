@extends('layouts.admin')

@section('title', 'New Brand')

@section('content')
    <h1 class="h4 mb-3">New Brand</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.brands.store') }}" method="POST" enctype="multipart/form-data">
                @include('admin.brands._form')
            </form>
        </div>
    </div>
@endsection
