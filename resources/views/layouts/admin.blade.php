<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Admin - ' . config('app.name', 'Laravel'))</title>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        @stack('head')
    </head>
    <body>
        <div class="d-flex" style="min-height: 100vh;">
            @include('partials.admin-nav')

            <div class="flex-grow-1 d-flex flex-column" style="min-height: 100vh;">
                <nav class="navbar navbar-expand navbar-dark bg-dark sticky-top">
                    <div class="container-fluid">
                        <span class="navbar-text text-white flex-grow-1 d-none d-lg-inline">@yield('title', 'Admin')</span>
                        <div class="d-lg-none flex-grow-1" style="padding-left: var(--sidebar-toggle-width);">
                            <a href="{{ route('admin.dashboard') }}" class="navbar-brand text-white text-decoration-none mb-0">
                                <i class="bi bi-shield-lock me-1"></i>{{ config('app.name', 'Laravel') }}
                            </a>
                        </div>
                        <button
                            class="btn btn-dark d-lg-none p-1"
                            type="button"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#adminSidebar"
                            aria-controls="adminSidebar"
                            aria-label="Toggle navigation"
                            style="width: var(--sidebar-toggle-width);"
                        >
                            <i class="bi bi-list fs-4"></i>
                        </button>
                    </div>
                </nav>

                <main class="container-fluid p-4 flex-grow-1">
                    <x-flash-messages />

                    @yield('content')
                </main>

                <footer class="navbar navbar-dark bg-dark px-4 py-2">
                    <span class="text-white-50 small">&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}</span>
                </footer>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('.js-delete-trigger');

                if (!trigger) return;

                if (!window.confirm(trigger.dataset.confirm || 'Are you sure?')) return;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = trigger.dataset.deleteUrl;
                form.style.display = 'none';

                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(token);

                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);

                document.body.appendChild(form);
                form.submit();
            });
        </script>

        @stack('scripts')
    </body>
</html>
