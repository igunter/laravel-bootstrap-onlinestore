<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductOptionController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($product) {
                    if ($product->options()->where('name', $value)->exists()) {
                        $fail('This product already has an option with that name.');
                    }
                },
            ],
            'values' => ['required', 'string'],
        ]);

        $values = $this->splitCsv($data['values']);

        if (empty($values)) {
            return back()->withInput()->with('error', 'Enter at least one value.');
        }

        $option = $product->options()->create([
            'name' => $data['name'],
            'position' => $product->options()->count(),
        ]);

        foreach ($values as $position => $value) {
            $option->values()->create(['value' => $value, 'position' => $position]);
        }

        return redirect()->route('admin.products.edit', $product)->with('success', count($values).' value(s) added under "'.$option->name.'".');
    }

    public function update(Request $request, Product $product, ProductOption $option): RedirectResponse
    {
        abort_unless($option->product_id === $product->id, 404);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($product, $option) {
                    if ($product->options()->whereKeyNot($option->id)->where('name', $value)->exists()) {
                        $fail('This product already has an option with that name.');
                    }
                },
            ],
        ]);

        $option->update($data);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Option renamed.');
    }

    public function destroy(Product $product, ProductOption $option): RedirectResponse
    {
        abort_unless($option->product_id === $product->id, 404);

        $inUse = $option->values()->whereHas('variants')->exists();

        if ($inUse) {
            return redirect()->route('admin.products.edit', $product)
                ->with('error', 'Delete the variants using this option\'s values first.');
        }

        $option->delete();

        return redirect()->route('admin.products.edit', $product)->with('success', 'Option deleted.');
    }

    /**
     * Split a comma-separated string into trimmed, non-empty, unique values.
     */
    private function splitCsv(string $csv): array
    {
        $values = array_map('trim', explode(',', $csv));
        $values = array_filter($values, fn ($value) => $value !== '');

        return array_values(array_unique($values));
    }
}
