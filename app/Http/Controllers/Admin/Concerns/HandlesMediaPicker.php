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

            if ($sourceMedia) {
                $this->copyToEndOfCollection($sourceMedia, $model, $collection);
            }
        }
    }

    /**
     * Apply images to a model with a multi-file collection, from any
     * combination of a direct file upload (each also copied into the media
     * library, tagged with $context) and/or media library items chosen via
     * a multi-select picker (field name "media_asset_ids").
     */
    private function applyPickedOrUploadedMultipleMedia(Request $request, HasMedia $model, string $collection, string $fileField, string $context): void
    {
        if ($request->hasFile($fileField)) {
            $model->addMultipleMediaFromRequest([$fileField])
                ->map(fn ($fileAdder) => $fileAdder->toMediaCollection($collection))
                ->each(fn (Media $media) => $this->registerInLibrary($media, $context));
        }

        $assetIds = collect($request->input('media_asset_ids', []))->filter();

        if ($assetIds->isNotEmpty()) {
            MediaAsset::whereIn('id', $assetIds)->get()->each(function (MediaAsset $asset) use ($model, $collection) {
                $sourceMedia = $asset->getFirstMedia('file');

                if ($sourceMedia) {
                    $this->copyToEndOfCollection($sourceMedia, $model, $collection);
                }
            });
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

    /**
     * Media::copy() sets the new media's order_column to match the source
     * media's, which can collide with (or precede) the destination
     * collection's existing items instead of appending after them. Force it
     * to the end of the destination collection instead.
     */
    private function copyToEndOfCollection(Media $sourceMedia, HasMedia $model, string $collection): Media
    {
        $nextOrder = ((int) $model->media()->where('collection_name', $collection)->max('order_column')) + 1;

        $newMedia = $sourceMedia->copy($model, $collection);
        $newMedia->order_column = $nextOrder;
        $newMedia->save();

        return $newMedia;
    }
}
