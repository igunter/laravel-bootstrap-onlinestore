<div class="bg-dark text-white p-3" style="width: 220px; min-height: 100vh;">
    <a href="{{ route('admin.dashboard') }}" class="d-block text-white text-decoration-none fs-5 mb-4">
        <i class="bi bi-shield-lock me-1"></i>{{ config('app.name', 'Laravel') }}
    </a>

    <ul class="nav nav-pills flex-column">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : 'text-white' }}" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
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
    </ul>
</div>
