<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HandlesMediaPicker
{
    /**
     * Apply an image to the given model, either from a direct file upload
     * (which also gets copied into the media library, tagged with $context)
     * or from a media library item chosen via the picker.
     */
    private function applyPickedOrUploadedMedia(Request $request, HasMedia $model, string $collection, string $fileField, string $context): void
    {
        if ($request->hasFile($fileField)) {
            $media = $model->addMediaFromRequest($fileField)->toMediaCollection($collection);

            $this->registerInLibrary($media, $context);

            return;
        }

        if ($request->filled('media_asset_id')) {
            $sourceMedia = MediaAsset::find($request->input('media_asset_id'))?->getFirstMedia('file');

            $sourceMedia?->copy($model, $collection);
        }
    }

    private function registerInLibrary(Media $media, string $context): void
    {
        $asset = MediaAsset::create([
            'name' => $media->name,
            'usable_for' => [$context],
        ]);

        $media->copy($asset, 'file');
    }
}
