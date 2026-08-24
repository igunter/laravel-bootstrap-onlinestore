# E-commerce build tracker

Two-sided store: admin manages a nested category tree, brands, products with
variants (e.g. Size/Color), and media; customers browse and buy, checking out
through SumUp. Built in 7 phases so the app stays working and testable after
each one — **build and verify in order, don't skip ahead**.

Full detailed plan (schema, exact file paths, package list) lives at
`C:\Users\Ian\.claude\plans\breezy-beaming-meadow.md`. This file is the
quick-reference status tracker.

## Locked-in decisions

- Admin access: plain `role` column on `users` (no spatie/permission).
- Products have real variants (options + values + variant SKUs), not simple flat products.
- Categories: `kalnoy/nestedset` (arbitrary depth).
- Media: `spatie/laravel-medialibrary`.
- Cart: session-based, no DB cart table.
- Payment: **SumUp only** for now, via their REST API (no official SDK) — OAuth2
  client-credentials + Checkout API + webhook that re-verifies status server-side.
- No JS build tooling — pure Blade + Bootstrap 5 (CDN) + jQuery (CDN), no
  Vite/Alpine/Livewire. Admin UI built the same way, full page reloads.

## Progress

- [ ] **Phase 1 — Roles & admin gate**
  - `role` column on `users` (default `customer`), `App\Enums\UserRole` enum cast on `User`.
  - `User::isAdmin()`, `EnsureUserIsAdmin` middleware aliased `admin` in `bootstrap/app.php`.
  - `Gate::define('access-admin', ...)` in `AppServiceProvider`.
  - `routes/admin.php` (placeholder `/admin` dashboard route, `auth`+`verified`+`admin` middleware), required from `routes/web.php`.
  - Nav partials show "Admin" link only for admins.
  - `UserFactory::admin()` state.
  - Tests: `tests/Feature/Admin/AdminAccessTest.php` (guest→login redirect, customer→403, admin→200).
- [ ] **Phase 2 — Categories, Brands, Media**
  - `kalnoy/nestedset` + `spatie/laravel-medialibrary` + `intervention/image` + `intervention/image-driver-gd`.
  - `Category` (NodeTrait + InteractsWithMedia), `Brand` (InteractsWithMedia) models/migrations.
  - `resources/views/layouts/admin.blade.php` (new sidebar layout) + admin CRUD for both.
- [ ] **Phase 3 — Products & Variants**
  - `products`, `product_options`, `product_option_values`, `product_variants`, pivot table.
  - Admin CRUD: product → options/values → variants (SKU/price/stock) → images.
- [ ] **Phase 4 — Customer storefront browsing**
  - `routes/shop.php`, product/category browsing pages, variant picker on product show page.
- [ ] **Phase 5 — Cart**
  - `app/Support/Cart.php` (session-backed), cart routes/views.
- [ ] **Phase 6 — Checkout & Orders (no payment yet)**
  - `orders`/`order_items` tables, checkout form → pending order, customer order history, admin order view.
- [ ] **Phase 7 — SumUp integration**
  - `app/Services/SumUp/SumUpClient.php`, real checkout creation, webhook handler, stock decrement on payment confirmation.

## Notes for restarting

- Local dev DB currently has **no users** — register one, then promote via
  `php artisan tinker` → `User::where('email', '...')->update(['role' => 'admin'])`.
- After each phase: run `php artisan test`, then check this file's checkbox and
  hand it back to me to continue with the next phase.
