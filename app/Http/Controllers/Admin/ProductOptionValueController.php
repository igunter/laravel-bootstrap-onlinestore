<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductOptionValueController extends Controller
{
    public function store(Request $request, Product $product, ProductOption $option): RedirectResponse|JsonResponse
    {
        abort_unless($option->product_id === $product->id, 404);

        $data = $request->validate([
            'value' => ['required', 'string'],
        ]);

        $existing = $option->values()->pluck('value')->all();
        $values = array_diff($this->splitCsv($data['value']), $existing);

        if (empty($values)) {
            $message = 'Nothing to add — that value (or all of them) already exist.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $position = $option->values()->count();

        $created = [];
        foreach ($values as $value) {
            $created[] = $option->values()->create(['value' => $value, 'position' => $position++]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => count($created).' value(s) added.',
                'values' => collect($created)->map(fn (ProductOptionValue $value) => [
                    'id' => $value->id,
                    'value' => $value->value,
                ]),
            ]);
        }

        return redirect()->route('admin.products.edit', $product)->with('success', count($created).' value(s) added.');
    }

    public function destroy(Request $request, Product $product, ProductOption $option, ProductOptionValue $value): RedirectResponse|JsonResponse
    {
        abort_unless($option->product_id === $product->id, 404);
        abort_unless($value->product_option_id === $option->id, 404);

        if ($value->variants()->exists()) {
            $message = 'Delete the variants using this value first.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('admin.products.edit', $product)->with('error', $message);
        }

        $value->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Value deleted.']);
        }

        return redirect()->route('admin.products.edit', $product)->with('success', 'Value deleted.');
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
