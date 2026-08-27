<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private const TOTAL_PRODUCTS = 50;

    private const TOPS_CATEGORY_PRODUCT_COUNT = 45;

    private const VARIANT_PRODUCT_CHANCE = 75;

    private const OPTION_NAMES = ['Size', 'Color', 'Material'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topsCategory = Category::where('slug', 'men-tops')->first();

        $otherCategories = Category::get()
            ->filter(fn (Category $category) => $category->isLeaf() && $category->id !== $topsCategory?->id)
            ->values();

        $brands = Brand::all();

        if ($topsCategory) {
            for ($i = 0; $i < self::TOPS_CATEGORY_PRODUCT_COUNT; $i++) {
                $this->createProduct($topsCategory, $brands);
            }
        }

        $remaining = self::TOTAL_PRODUCTS - ($topsCategory ? self::TOPS_CATEGORY_PRODUCT_COUNT : 0);

        for ($i = 0; $i < $remaining; $i++) {
            $category = $otherCategories->isNotEmpty() ? $otherCategories->random() : null;

            $this->createProduct($category, $brands);
        }
    }

    private function createProduct(?Category $category, Collection $brands): void
    {
        $hasVariants = fake()->boolean(self::VARIANT_PRODUCT_CHANCE);

        $product = Product::factory()->create([
            'category_id' => $category?->id,
            'brand_id' => $brands->isNotEmpty() ? $brands->random()->id : null,
            'has_variants' => $hasVariants,
            'base_sku' => $hasVariants ? strtoupper(Str::random(8)) : null,
        ]);

        if ($hasVariants) {
            $this->createVariants($product);
        } else {
            ProductVariant::factory()->for($product)->create([
                'sku' => strtoupper($product->slug),
            ]);
        }
    }

    private function createVariants(Product $product): void
    {
        $optionName = fake()->randomElement(self::OPTION_NAMES);
        $valueCount = fake()->numberBetween(3, 10);

        $option = ProductOption::factory()->for($product)->create([
            'name' => $optionName,
            'position' => 0,
        ]);

        $values = collect($this->optionValues($optionName, $valueCount))
            ->values()
            ->map(fn (string $value, int $position) => $option->values()->create([
                'value' => $value,
                'position' => $position,
            ]));

        foreach ($values as $value) {
            $variant = ProductVariant::factory()->for($product)->create([
                'sku' => $product->base_sku.'-'.Str::slug($value->value),
                'price' => fake()->boolean(40) ? fake()->randomFloat(2, 5, 500) : null,
            ]);

            $variant->optionValues()->attach($value);
        }
    }

    /**
     * Pick $count non-repeating values for the given option, drawn from a fixed pool
     * per option name (rather than Faker's global unique(), which would eventually
     * run out of values across hundreds of products sharing the same word pool).
     */
    private function optionValues(string $optionName, int $count): array
    {
        return match ($optionName) {
            'Size' => array_slice(['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL', '6XL'], 0, $count),
            'Color' => collect([
                'Black', 'White', 'Red', 'Blue', 'Green', 'Yellow', 'Orange', 'Purple',
                'Pink', 'Grey', 'Brown', 'Navy', 'Beige', 'Maroon', 'Teal', 'Olive',
                'Cyan', 'Magenta', 'Gold', 'Silver',
            ])->shuffle()->take($count)->all(),
            default => collect([
                'Cotton', 'Polyester', 'Wool', 'Leather', 'Denim', 'Linen', 'Silk',
                'Nylon', 'Fleece', 'Suede', 'Velvet', 'Canvas', 'Cashmere', 'Spandex', 'Rayon',
            ])->shuffle()->take($count)->all(),
        };
    }
}
