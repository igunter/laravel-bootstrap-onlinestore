<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        // The cat/brand URL segments (see routes/shop.php) are resolved here rather than
        // via implicit route-model binding: Laravel passes already-bound models to the
        // controller positionally in URI order, which would silently swap $category and
        // $brand on the reversed products/brand/{brand}/cat/{category} route. The same
        // slugs are also accepted as ?category=/?brand= query params, so the filter
        // selects on this page can just do a plain GET without needing the two-segment
        // routes.
        $categorySlug = $request->route('category') ?: ($request->string('category')->value() ?: null);
        $brandSlug = $request->route('brand') ?: ($request->string('brand')->value() ?: null);

        $category = $categorySlug ? Category::where('slug', $categorySlug)->firstOrFail() : null;
        $brand = $brandSlug ? Brand::where('slug', $brandSlug)->firstOrFail() : null;

        abort_unless($category === null || $category->is_active, 404);
        abort_unless($brand === null || $brand->is_active, 404);

        // /page/{page} (see routes/shop.php) is also read manually, same reasoning as
        // category/brand above; falling back to the standard ?page= query string keeps
        // Laravel's own pagination machinery (e.g. direct API-style access) working too.
        $page = $request->route('page') ? (int) $request->route('page') : null;

        // Include products in the category's descendants too, not just direct matches
        // (matches CategoryController::show's behaviour).
        $categoryIds = $category ? $category->descendants()->pluck('id')->push($category->id) : null;

        $products = Product::query()
            ->where('is_active', true)
            ->when($categoryIds, fn ($query) => $query->whereIn('category_id', $categoryIds))
            ->when($brand, fn ($query) => $query->where('brand_id', $brand->id))
            ->with(['category', 'brand'])
            ->orderBy('name')
            ->paginate(12, ['*'], 'page', $page);

        [$routeName, $pagedRouteName, $routeParams] = match (true) {
            $category && $brand => ['shop.products.index.category_brand', 'shop.products.index.category_brand.page', ['category' => $category->slug, 'brand' => $brand->slug]],
            (bool) $category => ['shop.products.index.category', 'shop.products.index.category.page', ['category' => $category->slug]],
            (bool) $brand => ['shop.products.index.brand', 'shop.products.index.brand.page', ['brand' => $brand->slug]],
            default => ['shop.products.index', 'shop.products.index.page', []],
        };

        return view('shop.products.index', [
            'products' => $products,
            'categories' => $this->categoryOptions(),
            'brands' => Brand::where('is_active', true)
                ->whereHas('products', function ($query) use ($categoryIds) {
                    $query->where('is_active', true)
                        ->when($categoryIds, fn ($query) => $query->whereIn('category_id', $categoryIds));
                })
                ->orderBy('name')
                ->get(),
            'selectedCategorySlug' => $category?->slug,
            'selectedBrandSlug' => $brand?->slug,
            'categoryBreadcrumbs' => $category ? $category->ancestors()->defaultOrder()->get()->push($category) : collect(),
            'paginationRouteName' => $routeName,
            'paginationPagedRouteName' => $pagedRouteName,
            'paginationRouteParams' => $routeParams,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['category', 'brand', 'options.values', 'variants.optionValues.option', 'variants.media']);

        $variants = [];

        if ($product->has_variants) {
            foreach ($product->variants->where('is_active', true) as $variant) {
                $variants[] = [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => (float) $variant->effective_price,
                    'stock' => $variant->stock_quantity,
                    'values' => $variant->optionValues->pluck('id', 'product_option_id'),
                    // ProductVariant only registers a 'thumb' conversion (unlike
                    // Product's thumb/large), so the original file stands in for
                    // the "large" main-image URL here.
                    'images' => $variant->getMedia('images')->map(fn ($media) => [
                        'thumb' => $media->getUrl('thumb'),
                        'large' => $media->getUrl(),
                    ])->values(),
                ];
            }
        }

        return view('shop.products.show', [
            'product' => $product,
            'variants' => $variants,
            'categoryBreadcrumbs' => $product->category
                ? $product->category->ancestors()->defaultOrder()->get()->push($product->category)
                : collect(),
        ]);
    }

    /**
     * Active categories flattened from the tree in depth order, each carrying a
     * `depth` attribute so the filter select can indent children under their parent.
     * Only categories that (or whose descendants) have at least one active product
     * are included — an empty category clutters the filter without doing anything.
     */
    private function categoryOptions(): array
    {
        $tree = Category::where('is_active', true)->defaultOrder()->get()->toTree();

        $categoryIdsWithProducts = Product::where('is_active', true)
            ->whereNotNull('category_id')
            ->pluck('category_id')
            ->unique();

        $hasProducts = function (Category $node) use (&$hasProducts, $categoryIdsWithProducts) {
            if ($categoryIdsWithProducts->contains($node->id)) {
                return true;
            }

            return $node->children->contains(fn (Category $child) => $hasProducts($child));
        };

        $options = [];

        $flatten = function ($nodes, $depth = 0) use (&$flatten, &$options, $hasProducts) {
            foreach ($nodes as $node) {
                if (! $hasProducts($node)) {
                    continue;
                }

                $node->depth = $depth;
                $options[] = $node;

                $flatten($node->children, $depth + 1);
            }
        };

        $flatten($tree);

        return $options;
    }
}
