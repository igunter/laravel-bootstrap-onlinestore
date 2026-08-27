<ul class="navbar-nav ms-auto d-none d-md-flex align-items-md-center">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('shop.products.*') ? 'active' : '' }}" href="{{ route('shop.products.index') }}">
            <i class="bi bi-shop me-1"></i>Shop
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('cart.*') ? 'active' : '' }}" href="{{ route('cart.index') }}">
            <i class="bi bi-cart me-1"></i>Cart
            @php $cartCount = app(\App\Support\Cart::class)->count(); @endphp
            <span class="badge text-bg-primary rounded-pill cart-count-badge @if ($cartCount < 1) d-none @endif">{{ $cartCount }}</span>
        </a>
    </li>
    @auth
        @unless (auth()->user()->hasVerifiedEmail())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('verification.notice') ? 'active' : '' }}" href="{{ route('verification.notice') }}">
                    <i class="bi bi-envelope-check me-1"></i>Verify Email
                </a>
            </li>
        @endunless
        @if (auth()->user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-shield-lock me-1"></i>Admin
                </a>
            </li>
        @endif
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userMenuMd" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuMd">
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-box-arrow-right me-1"></i>Log out
                        </button>
                    </form>
                </li>
            </ul>
        </li>
    @else
        <li class="nav-item me-2">
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm {{ request()->routeIs('login') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-in-right me-1"></i>Log in
            </a>
        </li>
        @if (Route::has('register'))
            <li class="nav-item">
                <a href="{{ route('register') }}" class="btn btn-outline-light btn-sm {{ request()->routeIs('register') ? 'active' : '' }}">
                    <i class="bi bi-person-plus me-1"></i>Register
                </a>
            </li>
        @endif
    @endauth
</ul>
