<?php

namespace App\Support\Media;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Support\FileNamer\FileNamer;

/**
 * Names the original file after a fresh UUID (ignoring the uploaded file's own
 * name) and suffixes conversion files with "_{conversionName}" — e.g.
 * uuid.jpg for the original and uuid_thumb.jpg for its "thumb" conversion.
 * Paired with FlatPathGenerator so everything lands directly in the disk root.
 */
class UuidFileNamer extends FileNamer
{
    public function originalFileName(string $fileName): string
    {
        return (string) Str::uuid();
    }

    public function conversionFileName(string $fileName, Conversion $conversion): string
    {
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);

        return "{$baseName}_{$conversion->getName()}";
    }

    public function responsiveFileName(string $fileName): string
    {
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);

        return "{$baseName}_responsive";
    }
}
