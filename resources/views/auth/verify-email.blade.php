@extends('layouts.app')

@section('title', 'Verify Email - ' . config('app.name'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Verify your email address</h1>
                    <p class="text-muted small mb-4">
                        Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
                    </p>

                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Resend verification email</button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-link ps-0">Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
