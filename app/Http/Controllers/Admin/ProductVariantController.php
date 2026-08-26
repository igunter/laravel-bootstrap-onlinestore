<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductVariantController extends Controller
{
    /**
     * Create any variants missing from the cartesian product of every
     * option's values. If the product has no options at all, ensure it has
     * a single default variant so it's always purchasable.
     */
    public function generate(Product $product): RedirectResponse
    {
        $product->load('options.values', 'variants.optionValues');

        $valueGroups = $product->options->pluck('values')->filter(fn ($values) => $values->isNotEmpty());

        $baseSku = $product->base_sku ?: Str::slug($product->name);

        if ($valueGroups->isEmpty()) {
            if ($product->variants->isEmpty()) {
                $variant = $product->variants()->create([
                    'sku' => $this->uniqueSku($baseSku),
                    'price' => $product->base_price,
                    'stock_quantity' => 0,
                    'is_active' => true,
                ]);

                $this->copyProductImagesToVariant($product, $variant);

                return redirect()->route('admin.products.edit', $product)->with('success', 'Default variant created.');
            }

            return redirect()->route('admin.products.edit', $product)->with('error', 'This product has no options to generate combinations from.');
        }

        $combinations = $valueGroups->reduce(function (?Collection $carry, $values) {
            $ids = $values->pluck('id');

            return $carry === null
                ? $ids->map(fn ($id) => [$id])
                : $carry->flatMap(fn ($combo) => $ids->map(fn ($id) => [...$combo, $id]));
        }, null);

        $existingCombos = $product->variants
            ->map(fn (ProductVariant $variant) => $variant->optionValues->pluck('id')->sort()->values()->all())
            ->all();

        $created = 0;

        foreach ($combinations as $combo) {
            sort($combo);

            if (in_array($combo, $existingCombos, true)) {
                continue;
            }

            $values = ProductOptionValue::whereIn('id', $combo)->get()->sortBy('id');
            $sku = $this->uniqueSku($baseSku.'-'.Str::slug($values->pluck('value')->implode('-')));

            $variant = $product->variants()->create([
                'sku' => $sku,
                'price' => $product->base_price,
                'stock_quantity' => 0,
                'is_active' => true,
            ]);

            $variant->optionValues()->attach($combo);
            $this->copyProductImagesToVariant($product, $variant);

            $existingCombos[] = $combo;
            $created++;
        }

        $message = $created > 0 ? "{$created} variant(s) generated." : 'All combinations already have a variant.';

        return redirect()->route('admin.products.edit', $product)->with('success', $message);
    }

    public function update(Request $request, Product $product, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:255', 'unique:product_variants,sku,'.$variant->id],
            'upc' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $variant->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Variant updated.',
                'variant' => $variant->fresh(),
            ]);
        }

        return redirect()->route('admin.products.edit', $product)->with('success', 'Variant updated.');
    }

    public function destroy(Request $request, Product $product, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $variant->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Variant deleted.']);
        }

        return redirect()->route('admin.products.edit', $product)->with('success', 'Variant deleted.');
    }

    /**
     * Seed a newly generated variant with copies of the product's own images,
     * so admins have a starting point and can delete/replace per variant.
     */
    private function copyProductImagesToVariant(Product $product, ProductVariant $variant): void
    {
        $product->getMedia('images')->each(fn (Media $media) => $media->copy($variant, 'images'));
    }

    private function uniqueSku(string $base): string
    {
        $base = strtoupper($base);
        $sku = $base;
        $i = 1;

        while (ProductVariant::where('sku', $sku)->exists()) {
            $sku = "{$base}-{$i}";
            $i++;
        }

        return $sku;
    }
}
