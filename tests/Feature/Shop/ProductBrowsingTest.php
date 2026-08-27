<?php

namespace Tests\Feature\Shop;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_lists_active_products(): void
    {
        Product::factory()->create(['name' => 'Visible Product', 'is_active' => true]);
        Product::factory()->create(['name' => 'Hidden Product', 'is_active' => false]);

        $response = $this->get(route('shop.products.index'));

        $response->assertOk();
        $response->assertSee('Visible Product');
        $response->assertDontSee('Hidden Product');
    }

    public function test_index_page_2_uses_the_page_path_pattern(): void
    {
        Product::factory()->count(13)->create(['is_active' => true]);

        $response = $this->get(route('shop.products.index'));
        $response->assertOk();
        $response->assertSee('/products/page/2', false);

        // Page 1 canonicalizes back to the plain (un-paginated-looking) URL.
        $page2 = $this->get('/products/page/2');
        $page2->assertOk();
        $page2->assertSee('href="'.route('shop.products.index').'"', false);
    }

    public function test_index_page_path_combines_with_category_filter(): void
    {
        $category = Category::factory()->create(['slug' => 'widgets']);
        Product::factory()->count(13)->create(['category_id' => $category->id, 'is_active' => true]);

        $response = $this->get('/products/category/widgets');
        $response->assertOk();
        $response->assertSee('/products/category/widgets/page/2', false);

        $this->get('/products/category/widgets/page/2')->assertOk();
    }

    public function test_index_can_be_filtered_by_category(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();
        Product::factory()->create(['name' => 'In Category A', 'category_id' => $categoryA->id, 'is_active' => true]);
        Product::factory()->create(['name' => 'In Category B', 'category_id' => $categoryB->id, 'is_active' => true]);

        $response = $this->get(route('shop.products.index', ['category' => $categoryA->slug]));

        $response->assertOk();
        $response->assertSee('In Category A');
        $response->assertDontSee('In Category B');
    }

    public function test_index_category_filter_includes_descendant_categories_products(): void
    {
        $parent = Category::factory()->create(['slug' => 'electronics']);
        $child = Category::factory()->create(['slug' => 'laptops', 'parent_id' => $parent->id]);
        $other = Category::factory()->create(['slug' => 'clothing']);
        Product::factory()->create(['name' => 'Direct Parent Product', 'category_id' => $parent->id, 'is_active' => true]);
        Product::factory()->create(['name' => 'Child Category Product', 'category_id' => $child->id, 'is_active' => true]);
        Product::factory()->create(['name' => 'Unrelated Product', 'category_id' => $other->id, 'is_active' => true]);

        $response = $this->get('/products/category/electronics');

        $response->assertOk();
        $response->assertSee('Direct Parent Product');
        $response->assertSee('Child Category Product');
        $response->assertDontSee('Unrelated Product');
    }

    public function test_index_shows_breadcrumbs_to_parent_categories_when_filtered(): void
    {
        $grandparent = Category::factory()->create(['slug' => 'clothing', 'name' => 'Clothing']);
        $parent = Category::factory()->create(['slug' => 'mens', 'name' => 'Mens', 'parent_id' => $grandparent->id]);
        $child = Category::factory()->create(['slug' => 'shirts', 'name' => 'Shirts', 'parent_id' => $parent->id]);

        $response = $this->get('/products/category/shirts');

        $response->assertOk();
        $response->assertSee('<nav aria-label="breadcrumb"', false);
        $response->assertSeeInOrder(['Shop', 'Clothing', 'Mens', 'Shirts']);
        $response->assertSee('href="'.route('shop.products.index.category', ['category' => 'clothing']).'"', false);
        $response->assertSee('href="'.route('shop.products.index.category', ['category' => 'mens']).'"', false);
    }

    public function test_index_shows_no_breadcrumbs_when_not_filtered_by_category(): void
    {
        $response = $this->get(route('shop.products.index'));

        $response->assertOk();
        $response->assertDontSee('<nav aria-label="breadcrumb"', false);
    }

    public function test_index_category_select_only_shows_categories_with_products(): void
    {
        $withProducts = Category::factory()->create(['name' => 'Has Products']);
        $empty = Category::factory()->create(['name' => 'Empty Category']);
        Product::factory()->create(['category_id' => $withProducts->id, 'is_active' => true]);

        $response = $this->get(route('shop.products.index'));

        $response->assertOk();
        $response->assertSee('Has Products');
        $response->assertDontSee('Empty Category');
    }

    public function test_index_category_select_shows_parent_when_only_a_child_has_products(): void
    {
        $parent = Category::factory()->create(['name' => 'Parent Only']);
        $child = Category::factory()->create(['name' => 'Child With Products', 'parent_id' => $parent->id]);
        Product::factory()->create(['category_id' => $child->id, 'is_active' => true]);

        $response = $this->get(route('shop.products.index'));

        $response->assertOk();
        $response->assertSee('Parent Only');
        $response->assertSee('Child With Products');
    }

    public function test_index_brand_select_only_shows_brands_with_products(): void
    {
        $withProducts = Brand::factory()->create(['name' => 'Brand With Products']);
        $empty = Brand::factory()->create(['name' => 'Empty Brand']);
        Product::factory()->create(['brand_id' => $withProducts->id, 'is_active' => true]);

        $response = $this->get(route('shop.products.index'));

        $response->assertOk();
        $response->assertSee('Brand With Products');
        $response->assertDontSee('Empty Brand');
    }

    public function test_index_brand_select_is_scoped_to_the_selected_category(): void
    {
        $categoryA = Category::factory()->create(['slug' => 'category-a']);
        $categoryB = Category::factory()->create(['slug' => 'category-b']);
        $brandInA = Brand::factory()->create(['name' => 'Brand In A']);
        $brandInB = Brand::factory()->create(['name' => 'Brand In B']);
        Product::factory()->create(['category_id' => $categoryA->id, 'brand_id' => $brandInA->id, 'is_active' => true]);
        Product::factory()->create(['category_id' => $categoryB->id, 'brand_id' => $brandInB->id, 'is_active' => true]);

        $response = $this->get('/products/category/category-a');

        $response->assertOk();
        $response->assertSee('Brand In A');
        $response->assertDontSee('Brand In B');
    }

    public function test_index_brand_select_is_scoped_to_category_descendants(): void
    {
        $parent = Category::factory()->create(['slug' => 'parent-cat']);
        $child = Category::factory()->create(['slug' => 'child-cat', 'parent_id' => $parent->id]);
        $other = Category::factory()->create(['slug' => 'other-cat']);
        $brandInChild = Brand::factory()->create(['name' => 'Brand In Child']);
        $brandInOther = Brand::factory()->create(['name' => 'Brand In Other']);
        Product::factory()->create(['category_id' => $child->id, 'brand_id' => $brandInChild->id, 'is_active' => true]);
        Product::factory()->create(['category_id' => $other->id, 'brand_id' => $brandInOther->id, 'is_active' => true]);

        $response = $this->get('/products/category/parent-cat');

        $response->assertOk();
        $response->assertSee('Brand In Child');
        $response->assertDontSee('Brand In Other');
    }

    public function test_index_filter_selects_use_slugs_as_option_values(): void
    {
        $category = Category::factory()->create(['slug' => 'widgets', 'name' => 'Widgets']);
        $brand = Brand::factory()->create(['slug' => 'acme', 'name' => 'Acme']);
        Product::factory()->create(['category_id' => $category->id, 'brand_id' => $brand->id, 'is_active' => true]);

        $response = $this->get(route('shop.products.index', ['category' => $category->slug]));

        $response->assertOk();
        $response->assertSee('<option value="widgets" selected', false);
        $response->assertSee('<option value="acme"', false);
    }

    public function test_index_can_be_filtered_by_category_slug_then_brand_slug_in_url(): void
    {
        $category = Category::factory()->create(['slug' => 'widgets']);
        $brand = Brand::factory()->create(['slug' => 'acme']);
        $otherBrand = Brand::factory()->create(['slug' => 'other']);
        Product::factory()->create(['name' => 'Matching Product', 'category_id' => $category->id, 'brand_id' => $brand->id, 'is_active' => true]);
        Product::factory()->create(['name' => 'Wrong Brand Product', 'category_id' => $category->id, 'brand_id' => $otherBrand->id, 'is_active' => true]);

        $response = $this->get('/products/category/widgets/brand/acme');

        $response->assertOk();
        $response->assertSee('Matching Product');
        $response->assertDontSee('Wrong Brand Product');
    }

    public function test_index_can_be_filtered_by_brand_slug_then_category_slug_in_url(): void
    {
        $category = Category::factory()->create(['slug' => 'widgets']);
        $otherCategory = Category::factory()->create(['slug' => 'gadgets']);
        $brand = Brand::factory()->create(['slug' => 'acme']);
        Product::factory()->create(['name' => 'Matching Product', 'category_id' => $category->id, 'brand_id' => $brand->id, 'is_active' => true]);
        Product::factory()->create(['name' => 'Wrong Category Product', 'category_id' => $otherCategory->id, 'brand_id' => $brand->id, 'is_active' => true]);

        $response = $this->get('/products/brand/acme/category/widgets');

        $response->assertOk();
        $response->assertSee('Matching Product');
        $response->assertDontSee('Wrong Category Product');
    }

    public function test_index_url_filters_404_for_inactive_category_or_brand(): void
    {
        $category = Category::factory()->create(['slug' => 'widgets', 'is_active' => false]);
        $brand = Brand::factory()->create(['slug' => 'acme']);

        $this->get('/products/category/widgets/brand/acme')->assertNotFound();
    }

    public function test_index_can_be_filtered_by_category_slug_only_in_url(): void
    {
        Category::factory()->create(['slug' => 'widgets'])->products()->save(
            Product::factory()->make(['name' => 'Widget Product', 'is_active' => true])
        );
        Product::factory()->create(['name' => 'Other Product', 'is_active' => true]);

        $response = $this->get('/products/category/widgets');

        $response->assertOk();
        $response->assertSee('Widget Product');
        $response->assertDontSee('Other Product');
    }

    public function test_index_can_be_filtered_by_brand_slug_only_in_url(): void
    {
        $brand = Brand::factory()->create(['slug' => 'acme']);
        Product::factory()->create(['name' => 'Acme Product', 'brand_id' => $brand->id, 'is_active' => true]);
        Product::factory()->create(['name' => 'Other Product', 'is_active' => true]);

        $response = $this->get('/products/brand/acme');

        $response->assertOk();
        $response->assertSee('Acme Product');
        $response->assertDontSee('Other Product');
    }

    public function test_category_page_includes_descendant_categories_products(): void
    {
        $parent = Category::factory()->create(['name' => 'Electronics']);
        $child = Category::factory()->create(['name' => 'Laptops', 'parent_id' => $parent->id]);
        Product::factory()->create(['name' => 'Direct Child Product', 'category_id' => $parent->id, 'is_active' => true]);
        Product::factory()->create(['name' => 'Grandchild Product', 'category_id' => $child->id, 'is_active' => true]);

        $response = $this->get(route('shop.categories.show', $parent));

        $response->assertOk();
        $response->assertSee('Direct Child Product');
        $response->assertSee('Grandchild Product');
    }

    public function test_category_page_pagination_uses_the_page_path_pattern(): void
    {
        $category = Category::factory()->create(['slug' => 'widgets']);
        Product::factory()->count(13)->create(['category_id' => $category->id, 'is_active' => true]);

        $response = $this->get(route('shop.categories.show', $category));
        $response->assertOk();
        $response->assertSee("/categories/{$category->slug}/page/2", false);

        $this->get("/categories/{$category->slug}/page/2")->assertOk();
    }

    public function test_inactive_category_returns_404(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $this->get(route('shop.categories.show', $category))->assertNotFound();
    }

    public function test_standalone_product_show_page_renders_price_and_stock(): void
    {
        $product = Product::factory()->create(['name' => 'Simple Widget', 'base_price' => 19.99, 'is_active' => true]);
        ProductVariant::factory()->for($product)->create(['stock_quantity' => 7, 'is_active' => true]);

        $response = $this->get(route('shop.products.show', $product));

        $response->assertOk();
        $response->assertSee('Simple Widget');
        $response->assertSee('19.99');
        $response->assertSee('7 in stock');
    }

    public function test_standalone_product_show_page_shows_full_category_breadcrumbs(): void
    {
        $grandparent = Category::factory()->create(['slug' => 'clothing', 'name' => 'Clothing']);
        $parent = Category::factory()->create(['slug' => 'mens', 'name' => 'Mens', 'parent_id' => $grandparent->id]);
        $child = Category::factory()->create(['slug' => 'shirts', 'name' => 'Shirts', 'parent_id' => $parent->id]);
        $product = Product::factory()->create(['name' => 'Oxford Shirt', 'category_id' => $child->id, 'is_active' => true]);

        $response = $this->get(route('shop.products.show', $product));

        $response->assertOk();
        $response->assertSeeInOrder(['Shop', 'Clothing', 'Mens', 'Shirts', 'Oxford Shirt']);
        $response->assertSee('href="'.route('shop.categories.show', $grandparent).'"', false);
        $response->assertSee('href="'.route('shop.categories.show', $parent).'"', false);
        $response->assertSee('href="'.route('shop.categories.show', $child).'"', false);
    }

    public function test_variant_product_show_page_embeds_correct_variant_price_and_stock(): void
    {
        $product = Product::factory()->create(['name' => 'Hoodie', 'base_price' => 40, 'has_variants' => true, 'is_active' => true]);
        $size = ProductOption::factory()->for($product)->create(['name' => 'Size']);
        $small = ProductOptionValue::factory()->for($size, 'option')->create(['value' => 'S']);
        $variant = ProductVariant::factory()->for($product)->create(['price' => 45, 'stock_quantity' => 3]);
        $variant->optionValues()->attach($small);

        $response = $this->get(route('shop.products.show', $product));

        $response->assertOk();
        $response->assertSee('Hoodie');
        $response->assertSee('"id":'.$variant->id, false);
        $response->assertSee('"price":45', false);
        $response->assertSee('"stock":3', false);
        $response->assertSee('"values":{"'.$size->id.'":'.$small->id.'}', false);
    }

    public function test_variant_product_show_page_embeds_each_variants_own_images(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create(['name' => 'Hoodie', 'has_variants' => true, 'is_active' => true]);
        $product->addMedia(UploadedFile::fake()->image('product.jpg'))->preservingOriginal()->toMediaCollection('images');

        $size = ProductOption::factory()->for($product)->create(['name' => 'Size']);
        $small = ProductOptionValue::factory()->for($size, 'option')->create(['value' => 'S']);
        $variant = ProductVariant::factory()->for($product)->create();
        $variant->optionValues()->attach($small);
        $variant->addMedia(UploadedFile::fake()->image('variant.jpg'))->preservingOriginal()->toMediaCollection('images');

        $response = $this->get(route('shop.products.show', $product));

        $response->assertOk();

        preg_match('/id="variant-data" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $variants = json_decode($matches[1], true);

        $variantMedia = $variant->fresh()->getFirstMedia('images');
        $this->assertSame([
            ['thumb' => $variantMedia->getUrl('thumb'), 'large' => $variantMedia->getUrl()],
        ], $variants[0]['images']);

        // The variant's own image should differ from the product's own — each
        // has its own separate copy, not a shared reference.
        $productMedia = $product->fresh()->getFirstMedia('images');
        $this->assertNotSame($productMedia->getUrl(), $variantMedia->getUrl());
    }

    public function test_variant_product_show_page_omits_inactive_variants(): void
    {
        $product = Product::factory()->create(['name' => 'Hoodie', 'has_variants' => true, 'is_active' => true]);
        $size = ProductOption::factory()->for($product)->create(['name' => 'Size']);
        $small = ProductOptionValue::factory()->for($size, 'option')->create(['value' => 'S']);
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'INACTIVE-SKU', 'is_active' => false]);
        $variant->optionValues()->attach($small);

        $response = $this->get(route('shop.products.show', $product));

        $response->assertOk();
        $response->assertDontSee('INACTIVE-SKU');
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->get(route('shop.products.show', $product))->assertNotFound();
    }
}
