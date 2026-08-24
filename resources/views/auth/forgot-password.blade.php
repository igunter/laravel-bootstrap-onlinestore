@extends('layouts.app')

@section('title', 'Forgot Password - ' . config('app.name'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3 text-center">Forgot your password?</h1>
                    <p class="text-muted small mb-4">
                        No problem. Let us know your email address and we will email you a password reset link.
                    </p>

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Email password reset link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
