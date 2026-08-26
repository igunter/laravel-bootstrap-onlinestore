<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesMediaPicker;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductVariantImageController extends Controller
{
    use HandlesMediaPicker;

    public function store(Request $request, Product $product, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $request->validate([
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],
            'media_asset_ids' => ['nullable', 'array'],
            'media_asset_ids.*' => ['integer', 'exists:media_assets,id'],
        ]);

        $this->applyPickedOrUploadedMultipleMedia($request, $variant, 'images', 'images', 'product');

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Variant image(s) added.',
                'images' => $this->mediaPayload($variant),
            ]);
        }

        return redirect()->route('admin.products.edit', $product)->with('success', 'Variant image(s) added.');
    }

    public function destroy(Request $request, Product $product, ProductVariant $variant, Media $media): RedirectResponse|JsonResponse
    {
        abort_unless($variant->product_id === $product->id, 404);
        abort_unless($media->model_type === ProductVariant::class && $media->model_id === $variant->id, 404);

        $media->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Variant image removed.',
                'images' => $this->mediaPayload($variant),
            ]);
        }

        return redirect()->route('admin.products.edit', $product)->with('success', 'Variant image removed.');
    }

    public function reorder(Request $request, Product $product, ProductVariant $variant): JsonResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        $currentIds = $variant->getMedia('images')->pluck('id')->sort()->values()->all();
        $givenIds = collect($data['order'])->map(fn ($id) => (int) $id)->sort()->values()->all();

        abort_unless($currentIds === $givenIds, 422);

        Media::setNewOrder($data['order']);

        return response()->json(['message' => 'Order updated.']);
    }

    private function mediaPayload(ProductVariant $variant): array
    {
        return $variant->fresh()->getMedia('images')->map(fn (Media $media) => [
            'id' => $media->id,
            'thumb_url' => $media->getUrl('thumb'),
        ])->all();
    }
}
