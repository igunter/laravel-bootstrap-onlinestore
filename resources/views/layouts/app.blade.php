<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Laravel'))</title>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">

        @stack('head')
    </head>
    <body>
        <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
            <div class="container-xl">
                <a class="navbar-brand" href="{{ url('/') }}">{{ config('app.name', 'Laravel') }}</a>

                {{-- Mobile-only: the full cart link/badge already lives inside the
                     offcanvas menu (nav-links-sm), out of sight until it's opened —
                     this gives a glanceable cart indicator right in the header,
                     shown only when there's actually something in the cart. --}}
                @php $mobileCartCount = app(\App\Support\Cart::class)->count(); @endphp
                <a
                    href="{{ route('cart.index') }}"
                    class="d-md-none text-white ms-auto me-2 cart-indicator @if ($mobileCartCount < 1) d-none @endif"
                    title="Cart"
                >
                    <span class="position-relative d-inline-block">
                        <i class="bi bi-cart fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-primary cart-count-badge">
                            {{ $mobileCartCount }}
                        </span>
                    </span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvas" aria-controls="navbarOffcanvas" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                @include('partials.nav-links-md')
            </div>
        </nav>

        @include('partials.nav-links-sm')

        <main class="container-xl">
            <x-flash-messages />

            @yield('content')
        </main>

        <footer class="bg-dark text-white py-4 mt-auto">
            <div class="container-xl text-center small">
                &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.
            </div>
        </footer>

        @include('partials.reset-demo-data-modal')

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        @stack('scripts')
    </body>
</html>
