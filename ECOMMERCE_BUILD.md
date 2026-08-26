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

- [x] **Phase 1 — Roles & admin gate** *(done)*
  - `role` column on `users` (default `customer`), `App\Enums\UserRole` enum cast on `User`.
  - `User::isAdmin()`, `EnsureUserIsAdmin` middleware aliased `admin` in `bootstrap/app.php`.
  - `Gate::define('access-admin', ...)` in `AppServiceProvider`.
  - `routes/admin.php` (placeholder `/admin` dashboard route, `auth`+`verified`+`admin` middleware), required from `routes/web.php`.
  - Nav partials show "Admin" link only for admins.
  - `UserFactory::admin()` state.
  - Tests: `tests/Feature/Admin/AdminAccessTest.php` (guest→login redirect, customer→403, admin→200).
- [x] **Phase 2 — Categories, Brands, Media** *(done)*
  - Installed `kalnoy/nestedset`, `spatie/laravel-medialibrary`, `intervention/image` (v3, GD driver built in — v4 needs PHP 8.3, this project is on 8.2).
  - `categories` (nested set columns via `$table->nestedSet()`, `name`/`slug`/`description`/`is_active`) and `brands` (`name`/`slug`/`description`/`is_active`) migrations + `media` table (medialibrary).
  - `Category` (NodeTrait + InteractsWithMedia, media collection `image`), `Brand` (InteractsWithMedia, media collection `logo`) models — both with a `thumb` (300x300) conversion.
  - `resources/views/layouts/admin.blade.php` (new sidebar layout) + `partials/admin-nav.blade.php`.
  - `Admin\DashboardController`, `Admin\CategoryController`, `Admin\BrandController` (full resources minus `show`) + views under `resources/views/admin/{categories,brands}/`. Category form has a parent-category picker (indented tree) and recursive `_rows.blade.php` partial for the nested index table.
  - `CategoryFactory`, `BrandFactory`.
  - Tests: `tests/Feature/Admin/CategoryManagementTest.php` (root + nested child creation, slug uniqueness, view rendering, customer 403) and `BrandManagementTest.php` (same pattern). All 28 tests pass.
- [x] **Phase 3 — Products & Variants** *(done)*
  - `products` (`category_id`/`brand_id` nullable FK nullOnDelete, `name`/`slug`/`description`/`base_price`/`is_active`), `product_options` (unique `product_id`+`name`), `product_option_values` (unique `product_option_id`+`value`), `product_variants` (`sku` unique, nullable `price`, `stock_quantity`, `is_active`), pivot `product_variant_option_value` (composite PK).
  - `Product` (InteractsWithMedia, collection `images`, `thumb`/`large` conversions, `category`/`brand`/`options`/`variants` relations), `ProductOption`, `ProductOptionValue`, `ProductVariant` (`effective_price` accessor falling back to product `base_price`, `optionsLabel()` helper) models.
  - `Admin\ProductController` (full resource) + `Admin\ProductImageController` (per-image delete), `Admin\ProductOptionController`/`ProductOptionValueController` (add/rename/delete options and values, blocked from deleting a value/option still used by a variant), `Admin\ProductVariantController` (`generate` computes the cartesian product of every option's values and creates any missing variants — or a single default variant if the product has no options — plus `update`/`destroy` per variant).
  - `resources/views/admin/products/{index,create,edit,_form}.blade.php` — edit page has Options and Variants sections below the core form; jQuery-free vanilla JS repeatable rows for adding option values when creating a new option; per-variant inline edit forms use the HTML5 `form=""` attribute (inputs live in table cells, forms live outside the table, since a `<form>` can't be a direct child of `<tr>`).
  - `ProductFactory`, `ProductOptionFactory`, `ProductOptionValueFactory`, `ProductVariantFactory`.
  - Added a "Products" sidebar link and dashboard count card.
  - Test: `tests/Feature/Admin/ProductManagementTest.php` (create, SKU uniqueness, `effective_price` fallback, cascading delete of options/values/variants, cartesian-product generation incl. no-duplicate-on-rerun, single default variant when no options, customer 403). All 35 tests pass.
  - Manually clicked through in-browser: created a product, added Size (S/M/L) and Color (Red/Blue) options, generated all 6 variants, edited one variant's SKU/price/stock inline and confirmed it persisted while the others correctly showed the base-price placeholder.
  - Follow-up refinement: added an explicit `has_variants` flag + "Standalone product" / "Has variants" radio choice on the product form (vanilla-JS toggle shows/hides SKU + stock quantity fields for standalone vs. the Options/Variants cards for has-variants). A standalone product still gets a single implicit `ProductVariant` created/kept in sync behind the scenes so cart/order code always references a variant either way. Switching an existing product to standalone is blocked (with a flash error) if it already has more than one variant.
- [ ] **Phase 4 — Customer storefront browsing**
  - `routes/shop.php`, product/category browsing pages, variant picker on product show page.
- [ ] **Phase 5 — Cart**
  - `app/Support/Cart.php` (session-backed), cart routes/views.
- [ ] **Phase 6 — Checkout & Orders (no payment yet)**
  - `orders`/`order_items` tables, checkout form → pending order, customer order history, admin order view.
- [ ] **Phase 7 — SumUp integration**
  - `app/Services/SumUp/SumUpClient.php`, real checkout creation, webhook handler, stock decrement on payment confirmation.

## Related uncommitted work (not part of the e-commerce build)

- Removed the old `/dashboard` route/view/nav link; login/register/email-verify
  now redirect back to the page the user was on before (session-based,
  `App\Http\Controllers\Auth\Concerns\RedirectsToIntendedUrl`), falling back to `/`.

## Notes for restarting

- Local dev DB currently has **no users** — register one, then promote via
  `php artisan tinker` → `User::where('email', '...')->update(['role' => 'admin'])`.
- After each phase: run `php artisan test`, then check this file's checkbox and
  hand it back to me to continue with the next phase.
