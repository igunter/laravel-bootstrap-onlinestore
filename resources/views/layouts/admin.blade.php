<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Admin - ' . config('app.name', 'Laravel'))</title>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">

        @stack('head')
    </head>
    <body>
        <div class="d-flex" style="min-height: 100vh;">
            @include('partials.admin-nav')

            <div class="flex-grow-1">
                <nav class="navbar navbar-expand navbar-dark bg-dark">
                    <div class="container-fluid">
                        <span class="navbar-text text-white">@yield('title', 'Admin')</span>
                        <a class="navbar-text text-white-50" href="{{ url('/') }}">
                            <i class="bi bi-box-arrow-left me-1"></i>Back to store
                        </a>
                    </div>
                </nav>

                <main class="container-fluid p-4">
                    <x-flash-messages />

                    @yield('content')
                </main>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        @stack('scripts')
    </body>
</html>
