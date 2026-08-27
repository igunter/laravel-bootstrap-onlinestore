<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MediaStorageLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_files_are_stored_flat_with_a_uuid_name(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create();
        $category->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection('image');

        $media = $category->fresh()->getFirstMedia('image');

        // Original: "images/{uuid}.jpg" — no "{id}/" subfolder.
        $this->assertMatchesRegularExpression(
            '#^images/[0-9a-f-]{36}\.jpg$#',
            $media->getPathRelativeToRoot()
        );
        $this->assertTrue(Str::isUuid(pathinfo($media->getPathRelativeToRoot(), PATHINFO_FILENAME)));

        // Conversion: "images/{uuid}_thumb.jpg" — same folder, no "conversions/" subfolder.
        $thumbPath = $media->getPathRelativeToRoot('thumb');
        $this->assertMatchesRegularExpression('#^images/[0-9a-f-]{36}_thumb\.jpg$#', $thumbPath);
        $this->assertSame(1, substr_count($thumbPath, '/'));
    }
}
