<?php

namespace Tests\Feature\Admin;

use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\ProductImageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductImageSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // The seeder always writes to the real cache/public directories (it's not
        // disk-faked, by design — it's meant to populate real storage), so clean
        // up what this test produced there.
        File::deleteDirectory(storage_path('app/seed-images'));
        File::deleteDirectory(storage_path('app/public/images'));

        parent::tearDown();
    }

    public function test_it_attaches_a_pre_resized_conversion_without_asking_spatie_to_resize(): void
    {
        Http::fake(fn () => Http::response('', 500));

        $product = Product::factory()->create();

        (new ProductImageSeeder)->run();

        $media = $product->fresh()->getFirstMedia('images');

        $this->assertNotNull($media);
        $this->assertTrue($media->hasGeneratedConversion('thumb'));
        $this->assertTrue($media->hasGeneratedConversion('large'));

        $this->assertFileExists($media->getPath());
        $this->assertFileExists($media->getPath('thumb'));
        $this->assertFileExists($media->getPath('large'));

        // Pool cache was populated on disk for reuse by the next seeder run.
        $this->assertFileExists(storage_path('app/seed-images/placeholder-0.jpg'));
        $this->assertFileExists(storage_path('app/seed-images/placeholder-0_thumb.jpg'));
        $this->assertFileExists(storage_path('app/seed-images/placeholder-0_large.jpg'));
    }

    public function test_variants_are_seeded_with_copies_of_the_parents_images(): void
    {
        Http::fake(fn () => Http::response('', 500));

        $product = Product::factory()->create(['has_variants' => true]);
        $variant = ProductVariant::factory()->for($product)->create();

        (new ProductImageSeeder)->run();

        $product->refresh();
        $variant->refresh();

        $this->assertTrue($product->getMedia('images')->isNotEmpty());

        $variantMedia = $variant->getFirstMedia('images');
        $this->assertNotNull($variantMedia);
        $this->assertSame($product->getMedia('images')->count(), $variant->getMedia('images')->count());

        // The variant only has its own copy — deleting it must not touch the
        // product's original file or media row.
        $this->assertNotSame($product->getFirstMedia('images')->id, $variantMedia->id);
        $this->assertFileExists($variantMedia->getPath());
        $this->assertFileExists($variantMedia->getPath('thumb'));

        $variantMedia->delete();

        $this->assertTrue($product->fresh()->getMedia('images')->isNotEmpty());
    }

    public function test_pool_images_are_made_available_in_the_media_library_picker(): void
    {
        Http::fake(fn () => Http::response('', 500));

        (new ProductImageSeeder)->run();

        $assets = MediaAsset::whereJsonContains('usable_for', 'product')->get();

        $this->assertSame(15, $assets->count());

        $asset = $assets->first();
        $this->assertNotNull($asset->getFirstMedia('file'));
        $this->assertFileExists($asset->getFirstMediaPath('file'));
        $this->assertFileExists($asset->getFirstMediaPath('file', 'thumb'));

        // Re-running shouldn't create duplicate assets for the same pool images.
        (new ProductImageSeeder)->run();
        $this->assertSame(15, MediaAsset::whereJsonContains('usable_for', 'product')->count());
    }
}
