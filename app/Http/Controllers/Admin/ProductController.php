<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesMediaPicker;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    use HandlesMediaPicker;

    public function index(): View
    {
        return view('admin.products.index', [
            'products' => Product::with(['category', 'brand'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'categoryOptions' => $this->categoryOptions(),
            'brandOptions' => Brand::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $standalone = $data['standalone'];
        unset($data['standalone']);

        $product = Product::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
        ]);

        if (! $product->has_variants) {
            $product->variants()->create([
                'sku' => $standalone['sku'],
                'upc' => $standalone['upc'],
                'stock_quantity' => $standalone['stock_quantity'],
                'is_active' => true,
            ]);
        }

        $this->applyPickedOrUploadedMultipleMedia($request, $product, 'images', 'images', 'product');

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load(['options.values', 'variants.optionValues.option']);

        return view('admin.products.edit', [
            'product' => $product,
            'categoryOptions' => $this->categoryOptions(),
            'brandOptions' => Brand::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        if ($request->boolean('has_variants') === false && $product->variants()->count() > 1) {
            return back()->withInput()->with('error', 'This product has multiple variants — delete all but one before switching it to standalone.');
        }

        $data = $this->validateData($request, $product);
        $standalone = $data['standalone'];
        unset($data['standalone']);

        if ($data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product);
        }

        $product->update($data);

        if (! $product->has_variants) {
            $product->variants()->updateOrCreate([], [
                'sku' => $standalone['sku'],
                'upc' => $standalone['upc'],
                'stock_quantity' => $standalone['stock_quantity'],
                'is_active' => true,
            ]);
        }

        $this->applyPickedOrUploadedMultipleMedia($request, $product, 'images', 'images', 'product');

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $product->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Product deleted.']);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    private function validateData(Request $request, ?Product $product = null): array
    {
        $standaloneVariantId = $product?->standaloneVariant()?->id;

        $skuUniqueRule = Rule::unique('product_variants', 'sku');

        if ($standaloneVariantId) {
            $skuUniqueRule = $skuUniqueRule->ignore($standaloneVariantId);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'has_variants' => ['required', Rule::in(['0', '1'])],
            'base_sku' => [Rule::requiredIf(fn () => $request->boolean('has_variants')), 'nullable', 'string', 'max:255'],
            'sku' => [Rule::requiredIf(fn () => ! $request->boolean('has_variants')), 'nullable', 'string', 'max:255', $skuUniqueRule],
            'upc' => ['nullable', 'string', 'max:255'],
            'stock_quantity' => [Rule::requiredIf(fn () => ! $request->boolean('has_variants')), 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],
            'media_asset_ids' => ['nullable', 'array'],
            'media_asset_ids.*' => ['integer', 'exists:media_assets,id'],
        ]);

        $standalone = [
            'sku' => $data['sku'] ?? null,
            'upc' => $data['upc'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
        ];

        unset($data['images'], $data['media_asset_ids'], $data['sku'], $data['upc'], $data['stock_quantity']);

        if ($request->boolean('has_variants')) {
            $data['base_sku'] = $data['base_sku'] ?? null;
        } else {
            $data['base_sku'] = null;
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['has_variants'] = $request->boolean('has_variants');
        $data['category_id'] = $data['category_id'] ?? null;
        $data['brand_id'] = $data['brand_id'] ?? null;
        $data['standalone'] = $standalone;

        return $data;
    }

    private function categoryOptions(): array
    {
        $tree = Category::defaultOrder()->get()->toTree();

        $options = [];

        $flatten = function ($nodes, $depth = 0) use (&$flatten, &$options) {
            foreach ($nodes as $node) {
                $options[$node->id] = str_repeat('— ', $depth).$node->name;
                $flatten($node->children, $depth + 1);
            }
        };

        $flatten($tree);

        return $options;
    }

    private function uniqueSlug(string $name, ?Product $ignoring = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::where('slug', $slug)->when($ignoring, fn ($q) => $q->whereKeyNot($ignoring->id))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
