<div
    class="offcanvas-lg offcanvas-start bg-dark text-white"
    tabindex="-1"
    id="adminSidebar"
    style="--bs-offcanvas-width: 220px;"
>
    {{--
        .navbar-brand's font-size/padding come from --bs-navbar-brand-* custom
        properties, which are normally only defined inside a .navbar. Set them
        here so the brand in this header matches the one in the top navbar exactly.
    --}}
    <div class="offcanvas-header d-lg-none" style="--bs-navbar-brand-font-size: 1.25rem; --bs-navbar-brand-padding-y: 0.3125rem; padding-top: 0.5rem; padding-bottom: 0.5rem;">
        <a href="{{ route('admin.dashboard') }}" class="navbar-brand text-white text-decoration-none mb-0">
            <i class="bi bi-shield-lock me-1"></i>{{ config('app.name', 'Laravel') }}
        </a>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button>
    </div>

    {{--
        Bootstrap forces .offcanvas-body { flex-grow: 0 } at the offcanvas-lg breakpoint,
        which leaves it only as tall as its own content. The sticky nav below needs this
        to span the sidebar's full height instead, so it has room to stay pinned for the
        whole scrollable page rather than releasing almost immediately.
    --}}
    <style>
        @media (min-width: 992px) {
            #adminSidebar {
                display: block;
                width: 212px;
                flex-shrink: 0;
            }
            #adminSidebar .offcanvas-body {
                height: 100%;
                flex-grow: 1 !important;
            }
        }
    </style>

    <div class="offcanvas-body px-3 d-flex flex-column">
        {{--
            Matches the top navbar's own brand box exactly (same --bs-navbar-brand-*
            vars and top padding as the navbar's 0.5rem), so the sidebar brand lines
            up with where the navbar's brand would sit, instead of sitting low.
        --}}
        <div
            class="sticky-lg-top bg-dark"
            style="top: 0; padding-top: 0.5rem; --bs-navbar-brand-font-size: 1.25rem; --bs-navbar-brand-padding-y: 0.3125rem;"
        >
            <a href="{{ route('admin.dashboard') }}" class="navbar-brand d-none d-lg-inline-block text-white text-decoration-none mb-4">
                <i class="bi bi-shield-lock me-1"></i>{{ config('app.name', 'Laravel') }}
            </a>

            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : 'text-white' }}" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : 'text-white' }}" href="{{ route('admin.products.index') }}">
                        <i class="bi bi-box-seam me-1"></i>Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : 'text-white' }}" href="{{ route('admin.categories.index') }}">
                        <i class="bi bi-diagram-3 me-1"></i>Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.brands.*') ? 'active' : 'text-white' }}" href="{{ route('admin.brands.index') }}">
                        <i class="bi bi-tags me-1"></i>Brands
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.media.*') ? 'active' : 'text-white' }}" href="{{ route('admin.media.index') }}">
                        <i class="bi bi-images me-1"></i>Media
                    </a>
                </li>
            </ul>
        </div>

        <div class="mt-auto sticky-bottom bg-dark">
            <hr class="text-white-50">

            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ url('/') }}">
                        <i class="bi bi-box-arrow-left me-1"></i>Back to store
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
