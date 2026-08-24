@extends('errors.layout')

@section('title', '429 Too Many Requests')

@section('content')
    <i class="bi bi-speedometer2 display-1 text-warning"></i>
    <h1 class="h3 mt-3">429 | Too Many Requests</h1>
    <p class="text-muted mb-0">You've made too many requests. Please slow down and try again shortly.</p>
@endsection
