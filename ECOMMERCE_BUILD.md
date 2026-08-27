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
- [x] **Phase 4 — Customer storefront browsing** *(done)*
  - `routes/shop.php` (required from `web.php`): `GET /products` (index, `?category=`/`?brand=` id filters), `GET /products/{product:slug}` (show), `GET /categories/{category:slug}` (show).
  - `App\Http\Controllers\ProductController`/`CategoryController` (customer-facing, root namespace vs `Admin\`) — both 404 on an inactive product/category. Category show pulls products for the category **and all its nestedset descendants** via `$category->descendants()`.
  - `resources/views/shop/products/{index,show}.blade.php`, `shop/categories/show.blade.php`, shared `shop/products/_card.blade.php` grid partial — extend `layouts/app.blade.php`. Product show page: for `has_variants` products, a per-option `<select>` plus a small vanilla-JS script reads a JSON map (`option-value-ids-key → {id, sku, price, stock, active}`) embedded in the page and updates displayed price/stock live with no API call; standalone products just show `base_price` + the implicit variant's stock directly.
  - Added a "Shop" nav link (visible to guests and logged-in users alike) to both `nav-links-md`/`nav-links-sm` partials.
  - Test: `tests/Feature/Shop/ProductBrowsingTest.php` (index lists only active products, category filter, category page includes nested descendants' products, inactive product/category → 404, standalone product shows correct price/stock, variant product's embedded JSON map has the right price/stock per combination). All 67 tests pass.
  - Manually verified in-browser: shop index with category/brand filters, category show page, standalone product page (price + stock), and the has-variants product page — selecting "S" showed £42.00/5 in stock, switching to "M" correctly updated to £44.00/Out of stock.
- [x] **Phase 5 — Cart** *(done)*
  - `app/Support/Cart.php` — session-backed, keyed by variant id (adding an already-present variant increments quantity rather than duplicating rows). `add()`/`update()`/`remove()`/`clear()`/`items()`/`subtotal()`/`count()`. Each row snapshots product name/slug, variant SKU/options-label/unit-price **at add time** (later price edits don't retroactively change what's in a cart), and quantity is capped at the variant's *current* `stock_quantity` on both add and update.
  - `app/Http/Controllers/CartController.php` (`index`/`store`/`update`/`destroy`/`clear`) + `routes/shop.php` (`cart.*`, prefix `cart`) — `store` rejects inactive variants/products and out-of-stock items with a flash error rather than a validation error, since it's not really "invalid input" from the shopper's POV.
  - `resources/views/shop/cart/index.blade.php` — line-item table with per-row quantity update/remove forms, subtotal, clear-cart button.
  - Product show page: "Add to cart" form wired in for both standalone and has-variants products — for has-variants, the existing option-select JS now also keeps a hidden `product_variant_id` input in sync and enables/disables the button based on the selected variant's live stock.
  - Cart nav link (both `nav-links-md`/`nav-links-sm` partials) with a quantity badge, visible to guests and logged-in users alike (cart doesn't require login).
  - Test: `tests/Feature/Shop/CartTest.php` (add twice increments quantity not duplicate rows, quantity capped at stock on add and on update, subtotal math, remove/clear, out-of-stock/inactive-variant/inactive-product rejected, row snapshots survive a later price change). All 102 tests pass.
  - Manually verified in-browser (via curl + a saved session cookie, since no browser automation available): added an item, confirmed the flash message and cart page's line item/subtotal/nav badge, removed the item, confirmed the empty-cart state; confirmed the has-variants product page's "Add to cart" button starts disabled until a valid option combination is selected.
- [x] **Phase 6 — Checkout & Orders (no payment yet)** *(done)*
  - `orders` (`user_id` nullable FK nullOnDelete, unique `order_number`, `status`, `subtotal`/`total`, `currency` default `GBP`, snapshotted contact/shipping columns, nullable `sumup_checkout_id` indexed + `paid_at`, both unused until Phase 7) and `order_items` (`order_id` cascadeOnDelete, `product_variant_id` nullOnDelete, snapshot columns `product_name`/`variant_sku`/`variant_options_label`/`unit_price`/`quantity`/`line_total`) migrations.
  - `App\Enums\OrderStatus` (pending/paid/failed/cancelled/fulfilled backed enum, plus `label()`/`badgeVariant()` helpers for the views).
  - `Order`/`OrderItem` models (`Order::generateOrderNumber()` — unique `ORD-XXXXXXXX` reference), `OrderFactory`/`OrderItemFactory`, `User::orders()` relation.
  - `app/Http/Controllers/CheckoutController.php` (`show`/`store`, both `auth`-gated in `routes/shop.php`) — `store` re-validates each cart row's stock against the *live* variant (not the cart's own snapshot, which could be stale) before committing, builds the Order+OrderItems from the cart's snapshotted data inside a `DB::transaction`, then clears the cart. The actual SumUp call is intentionally not wired in yet — `sumup_checkout_id` stays null this phase, per the plan.
  - Customer `app/Http/Controllers/OrderController.php` (`index`/`show`) + `resources/views/shop/orders/{index,show}.blade.php` — `show` uses a plain `abort_unless($order->user_id === auth()->id(), 403)` rather than a Policy class, consistent with how the rest of the app already does authorization checks (e.g. `ProductImageController`).
  - Admin `Admin\OrderController` (`index`/`show`/`update`) + `resources/views/admin/orders/{index,show}.blade.php` — `show` has a manual status `<select>` + submit, no SumUp involved yet.
  - "Checkout" button added to the cart page; "My Orders" link added to both nav partials' user dropdown; "Orders" link added to the admin sidebar.
  - Test: `tests/Feature/Shop/CheckoutTest.php` (guest redirected to login for both show/store, order+items created correctly from cart snapshot data, cart cleared after order, empty-cart redirects, stock-ran-out-since-adding-to-cart rejected, required-field validation) and `tests/Feature/Shop/OrderAuthorizationTest.php` (customer can't view another customer's order or the admin order routes, customer only sees their own orders in their index, admin can view any order). All 119 tests pass.
  - Manually verified in-browser (via curl + a saved session cookie): registered a customer, added an item to cart, loaded checkout with the name/email pre-filled, submitted shipping details, confirmed the order landed on its own show page with a "Pending" badge and the cart was cleared; confirmed the order appears in "My Orders"; confirmed a non-admin gets 403 on `/admin/orders`; as an admin, confirmed the orders index/show pages and that changing the status `<select>` to "Paid" persisted and updated the badge. Cleaned up the throwaway test account/order afterward.
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
