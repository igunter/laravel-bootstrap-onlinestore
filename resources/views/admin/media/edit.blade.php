@extends('layouts.admin')

@section('title', 'Edit Media')

@section('content')
    <h1 class="h4 mb-3">Edit Media</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.media.update', $asset) }}" method="POST" enctype="multipart/form-data">
                @method('PUT')
                @include('admin.media._form')
            </form>
        </div>
    </div>
@endsection
