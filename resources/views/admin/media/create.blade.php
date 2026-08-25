@extends('layouts.admin')

@section('title', 'New Media')

@section('content')
    <h1 class="h4 mb-3">New Media</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data">
                @include('admin.media._form')
            </form>
        </div>
    </div>
@endsection
