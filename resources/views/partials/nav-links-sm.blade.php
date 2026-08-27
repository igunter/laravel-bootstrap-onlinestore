<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="navbarOffcanvas" aria-labelledby="navbarOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="navbarOffcanvasLabel">{{ config('app.name', 'Laravel') }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#navbarOffcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <ul class="navbar-nav">
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
                    <a class="nav-link dropdown-toggle" href="#" id="userMenuSm" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuSm">
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
                <li class="nav-item mb-2">
                    <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Log in
                    </a>
                </li>
                @if (Route::has('register'))
                    <li class="nav-item">
                        <a href="{{ route('register') }}" class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}">
                            <i class="bi bi-person-plus me-1"></i>Register
                        </a>
                    </li>
                @endif
            @endauth
        </ul>

        @auth
            <div class="mt-auto pt-3">
                <hr class="border-secondary">
                <small class="text-white-50">{{ auth()->user()->name }}</small>
            </div>
        @endauth
    </div>
</div>
