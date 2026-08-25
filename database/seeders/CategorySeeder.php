<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tree = [
            'Women' => [
                'Tops',
                'Dresses',
                'Bottoms',
                'Outerwear',
                'Shoes',
            ],
            'Men' => [
                'Tops',
                'Bottoms',
                'Outerwear',
                'Shoes',
            ],
            'Kids' => [
                'Girls',
                'Boys',
            ],
            'Accessories' => [
                'Bags',
                'Jewellery',
                'Hats & Scarves',
            ],
            'Sale' => [],
        ];

        foreach ($tree as $name => $children) {
            $parent = $this->createCategory($name);

            foreach ($children as $childName) {
                $this->createCategory($childName, $parent);
            }
        }
    }

    private function createCategory(string $name, ?Category $parent = null): Category
    {
        $slug = Str::slug($parent ? "{$parent->name}-{$name}" : $name);

        return Category::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'is_active' => true,
                'parent_id' => $parent?->id,
            ],
        );
    }
}
