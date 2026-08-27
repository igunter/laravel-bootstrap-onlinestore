<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Stores every file directly under storage/app/public/images (e.g. images/uuid.ext)
 * instead of Spatie's default per-media subfolder (storage/app/public/{id}/...), so
 * originals and conversions all sit flat in one folder, distinguished only by their
 * filenames (see UuidFileNamer).
 */
class FlatPathGenerator implements PathGenerator
{
    private const DIRECTORY = 'images/';

    public function getPath(Media $media): string
    {
        return self::DIRECTORY;
    }

    public function getPathForConversions(Media $media): string
    {
        return self::DIRECTORY;
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return self::DIRECTORY;
    }
}
