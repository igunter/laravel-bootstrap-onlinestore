@extends('layouts.app')

@section('title', 'Dashboard - ' . config('app.name'))

@section('content')
    <div class="card">
        <div class="card-body">
            <h1 class="h4 mb-3">Dashboard</h1>
            <p class="mb-0">You're logged in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).</p>
        </div>
    </div>
@endsection
