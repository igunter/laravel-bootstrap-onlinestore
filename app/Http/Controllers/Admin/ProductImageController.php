<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductImageController extends Controller
{
    public function destroy(Product $product, Media $media): RedirectResponse
    {
        abort_unless($media->model_type === Product::class && $media->model_id === $product->id, 404);

        $media->delete();

        return redirect()->route('admin.products.edit', $product)->with('success', 'Image removed.');
    }

    public function reorder(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        $currentIds = $product->getMedia('images')->pluck('id')->sort()->values()->all();
        $givenIds = collect($data['order'])->map(fn ($id) => (int) $id)->sort()->values()->all();

        abort_unless($currentIds === $givenIds, 422);

        Media::setNewOrder($data['order']);

        return response()->json(['message' => 'Order updated.']);
    }
}
