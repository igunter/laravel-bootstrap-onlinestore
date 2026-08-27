<?php

namespace Database\Seeders;

use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductImageSeeder extends Seeder
{
    private const POOL_SIZE = 15;

    private const IMAGE_SIZE = 800;

    /**
     * Prefix for the MediaAsset name of each pool image, so re-runs can tell which
     * ones they already created (see seedMediaAssets()).
     */
    private const ASSET_NAME_PREFIX = 'Seeded placeholder ';

    /**
     * Mirrors Product::registerMediaConversions() — kept here so pool thumb/large
     * files can be pre-rendered once instead of letting Spatie resize them again
     * for every product that reuses the same pool image.
     */
    private const CONVERSIONS = [
        'thumb' => 300,
        'large' => 1200,
    ];

    /**
     * Solid-colour fallback palette, used only if picsum.photos can't be reached
     * (no network in this environment, offline dev, etc.) — keeps the seeder
     * runnable without a network dependency.
     */
    private const FALLBACK_COLORS = [
        [239, 108, 0], [3, 169, 244], [76, 175, 80], [156, 39, 176],
        [244, 67, 54], [0, 150, 136], [255, 193, 7], [63, 81, 181],
        [233, 30, 99], [96, 125, 139], [121, 85, 72], [0, 188, 212],
        [139, 195, 74], [255, 87, 34], [103, 58, 183],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pool = $this->buildImagePool();
        $this->seedMediaAssets($pool);

        $products = Product::with('variants')->get();

        $this->command?->getOutput()->progressStart($products->count());

        // A transaction batches the DB writes into one commit instead of one per
        // media row, which is what was actually making this slow (not the image
        // processing) — SQLite fsyncs on every commit otherwise.
        DB::transaction(function () use ($products, $pool) {
            foreach ($products as $product) {
                // Re-running this seeder tops products up to a (re-rolled) desired
                // count rather than skipping them outright, so a product that
                // already has 1 image from an earlier run can still gain a 2nd/3rd
                // — reusing (duplicating) pool images across products and even
                // within the same product is fine, they're just placeholders.
                $current = $product->getMedia('images')->count();
                $desired = $this->desiredImageCount();

                for ($i = $current; $i < $desired; $i++) {
                    $this->attachPooledImage($product, $pool[array_rand($pool)]);
                }

                // attachPooledImage() inserts Media rows directly rather than through
                // $product->addMedia(), so it never invalidates the "media" relation
                // that getMedia() above just cached (empty, if this was a fresh
                // product). Drop that cache so the variant copy below sees the
                // images we just attached.
                $product->unsetRelation('media');

                // Every variant starts out with copies of the parent's images, same
                // as clicking "Generate variants" in the admin does — the admin can
                // then remove them one by one per variant if they don't apply.
                foreach ($product->variants as $variant) {
                    if ($variant->getMedia('images')->isEmpty()) {
                        $this->copyProductImagesToVariant($product, $variant);
                    }
                }

                $this->command?->getOutput()->progressAdvance();
            }
        });

        $this->command?->getOutput()->progressFinish();
    }

    /**
     * Makes each pool image available in the admin's "Choose from library" media
     * picker (tagged "product"), so seeded demo data isn't only reachable by
     * uploading — it can be picked/reused from the library too, same as media an
     * admin added themselves. Skips any pool image that already has an asset from
     * a previous run.
     *
     * @param  list<array{original: string, thumb: string, large: string}>  $pool
     */
    private function seedMediaAssets(array $pool): void
    {
        $existingNames = MediaAsset::where('name', 'like', self::ASSET_NAME_PREFIX.'%')
            ->pluck('name')
            ->all();

        foreach ($pool as $index => $pooledImage) {
            $name = self::ASSET_NAME_PREFIX.($index + 1);

            if (in_array($name, $existingNames, true)) {
                continue;
            }

            $asset = MediaAsset::create([
                'name' => $name,
                'usable_for' => ['product'],
            ]);

            $this->attachAssetImage($asset, $pooledImage);
        }
    }

    /**
     * Copies a pool image (original + thumb — MediaAsset only registers a 'thumb'
     * conversion) onto a MediaAsset, the same cheap-file-copy way as the other
     * attach*() methods here.
     *
     * @param  array{original: string, thumb: string, large: string}  $pooledImage
     */
    private function attachAssetImage(MediaAsset $asset, array $pooledImage): void
    {
        $uuid = (string) Str::uuid();
        $extension = pathinfo($pooledImage['original'], PATHINFO_EXTENSION);
        $directory = storage_path('app/public/images');

        File::ensureDirectoryExists($directory);

        $fileName = "{$uuid}.{$extension}";
        File::copy($pooledImage['original'], "{$directory}/{$fileName}");
        File::copy($pooledImage['thumb'], "{$directory}/{$uuid}_thumb.{$extension}");

        $media = new Media;
        $media->model_type = MediaAsset::class;
        $media->model_id = $asset->id;
        $media->collection_name = 'file';
        $media->name = pathinfo($pooledImage['original'], PATHINFO_FILENAME);
        $media->file_name = $fileName;
        $media->mime_type = 'image/jpeg';
        $media->disk = 'public';
        $media->conversions_disk = 'public';
        $media->size = filesize($pooledImage['original']);
        $media->manipulations = [];
        $media->custom_properties = [];
        $media->generated_conversions = ['thumb' => true];
        $media->responsive_images = [];
        $media->save();
    }

    private function copyProductImagesToVariant(Product $product, ProductVariant $variant): void
    {
        foreach ($product->getMedia('images') as $media) {
            $this->attachCopiedImage($variant, $media);
        }
    }

    /**
     * Copies an already-seeded product image (original + thumb — ProductVariant
     * only registers a 'thumb' conversion, unlike Product's thumb/large) onto a
     * variant. Plain file copies, same as attachPooledImage(), so this stays cheap
     * even for products with many variants.
     */
    private function attachCopiedImage(ProductVariant $variant, Media $sourceMedia): void
    {
        $uuid = (string) Str::uuid();
        $extension = pathinfo($sourceMedia->file_name, PATHINFO_EXTENSION);
        $directory = storage_path('app/public/images');

        File::ensureDirectoryExists($directory);

        $fileName = "{$uuid}.{$extension}";
        File::copy($sourceMedia->getPath(), "{$directory}/{$fileName}");
        File::copy($sourceMedia->getPath('thumb'), "{$directory}/{$uuid}_thumb.{$extension}");

        $media = new Media;
        $media->model_type = ProductVariant::class;
        $media->model_id = $variant->id;
        $media->collection_name = 'images';
        $media->name = $sourceMedia->name;
        $media->file_name = $fileName;
        $media->mime_type = $sourceMedia->mime_type;
        $media->disk = 'public';
        $media->conversions_disk = 'public';
        $media->size = filesize($sourceMedia->getPath());
        $media->manipulations = [];
        $media->custom_properties = [];
        $media->generated_conversions = ['thumb' => true];
        $media->responsive_images = [];
        $media->save();
    }

    /**
     * Copies a pre-rendered pool image (and its pre-rendered thumb/large) straight
     * onto the disk and inserts the media row directly, instead of going through
     * addMedia()->toMediaCollection() — that would ask Spatie to resize the image
     * into a thumb/large conversion from scratch for every single product, which is
     * by far the slowest part of seeding. The pool images are already resized once
     * in buildImagePool(), so here we just copy the bytes and mark the conversions
     * as generated.
     *
     * @param  array{original: string, thumb: string, large: string}  $pooledImage
     */
    private function attachPooledImage(Product $product, array $pooledImage): void
    {
        $uuid = (string) Str::uuid();
        $extension = pathinfo($pooledImage['original'], PATHINFO_EXTENSION);
        $directory = storage_path('app/public/images');

        File::ensureDirectoryExists($directory);

        $fileName = "{$uuid}.{$extension}";
        File::copy($pooledImage['original'], "{$directory}/{$fileName}");

        $generatedConversions = [];

        foreach (self::CONVERSIONS as $name => $size) {
            File::copy($pooledImage[$name], "{$directory}/{$uuid}_{$name}.{$extension}");
            $generatedConversions[$name] = true;
        }

        $media = new Media;
        $media->model_type = Product::class;
        $media->model_id = $product->id;
        $media->collection_name = 'images';
        $media->name = pathinfo($pooledImage['original'], PATHINFO_FILENAME);
        $media->file_name = $fileName;
        $media->mime_type = 'image/jpeg';
        $media->disk = 'public';
        $media->conversions_disk = 'public';
        $media->size = filesize($pooledImage['original']);
        $media->manipulations = [];
        $media->custom_properties = [];
        $media->generated_conversions = $generatedConversions;
        $media->responsive_images = [];
        $media->save();
    }

    /**
     * Roughly half the products get just 1 image, a third get 2, and the rest get 3.
     */
    private function desiredImageCount(): int
    {
        return fake()->randomElement([1, 1, 1, 2, 2, 3]);
    }

    /**
     * @return list<array{original: string, thumb: string, large: string}> absolute
     *         paths to the pooled placeholder images, plus their pre-rendered
     *         thumb/large conversions
     *
     * Everything here is cached under storage/app/seed-images and reused across
     * runs (they're just demo placeholders, so there's no need to redownload or
     * re-resize them every time the seeder runs) — delete that folder if you want
     * fresh ones.
     */
    private function buildImagePool(): array
    {
        $directory = storage_path('app/seed-images');
        File::ensureDirectoryExists($directory);

        $pool = [];

        for ($i = 0; $i < self::POOL_SIZE; $i++) {
            $path = "{$directory}/placeholder-{$i}.jpg";

            if (! File::exists($path) && ! $this->downloadFromPicsum($i, $path)) {
                $this->renderFallback($i, $path);
            }

            $pooledImage = ['original' => $path];

            foreach (self::CONVERSIONS as $name => $size) {
                $conversionPath = "{$directory}/placeholder-{$i}_{$name}.jpg";

                if (! File::exists($conversionPath)) {
                    $this->renderResized($path, $conversionPath, $size);
                }

                $pooledImage[$name] = $conversionPath;
            }

            $pool[] = $pooledImage;
        }

        return $pool;
    }

    private function renderResized(string $sourcePath, string $destinationPath, int $size): void
    {
        $source = imagecreatefromjpeg($sourcePath);
        $resized = imagecreatetruecolor($size, $size);

        imagecopyresampled(
            $resized, $source,
            0, 0, 0, 0,
            $size, $size, self::IMAGE_SIZE, self::IMAGE_SIZE
        );

        imagejpeg($resized, $destinationPath);
        imagedestroy($source);
        imagedestroy($resized);
    }

    private function downloadFromPicsum(int $seed, string $path): bool
    {
        try {
            $response = Http::timeout(5)->get("https://picsum.photos/seed/product-{$seed}/".self::IMAGE_SIZE);

            if (! $response->successful()) {
                return false;
            }

            File::put($path, $response->body());

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function renderFallback(int $index, string $path): void
    {
        [$r, $g, $b] = self::FALLBACK_COLORS[$index % count(self::FALLBACK_COLORS)];

        $image = imagecreatetruecolor(self::IMAGE_SIZE, self::IMAGE_SIZE);
        imagefill($image, 0, 0, imagecolorallocate($image, $r, $g, $b));

        $white = imagecolorallocate($image, 255, 255, 255);
        $label = 'Product Photo '.($index + 1);
        $textWidth = imagefontwidth(5) * strlen($label);
        $x = (int) ((self::IMAGE_SIZE - $textWidth) / 2);
        $y = (int) (self::IMAGE_SIZE / 2);
        imagestring($image, 5, $x, $y, $label, $white);

        imagejpeg($image, $path);
        imagedestroy($image);
    }
}
