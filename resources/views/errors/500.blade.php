@extends('errors.layout')

@section('title', '500 Server Error')

@section('content')
    <i class="bi bi-exclamation-octagon display-1 text-danger"></i>
    <h1 class="h3 mt-3">500 | Server Error</h1>
    <p class="text-muted mb-0">Something went wrong on our end. Please try again later.</p>
@endsection
