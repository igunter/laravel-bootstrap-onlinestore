@extends('layouts.app')

@section('content')
    <div class="p-5 mb-4 bg-body-tertiary rounded-3 text-center">
        <h1 class="display-5 fw-bold">{{ config('app.name', 'Laravel') }}</h1>
        <p class="col-lg-8 mx-auto fs-5">
            A Laravel starter kit with Bootstrap, authentication, and everything else you need to get moving.
        </p>
        @guest
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-person-plus me-1"></i>Get started
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Log in
                </a>
            </div>
        @else
            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-speedometer2 me-1"></i>Go to dashboard
            </a>
        @endguest
    </div>
@endsection
