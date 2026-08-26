<?php

namespace Tests\Feature\Admin;

use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_standalone_product(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'T-Shirt',
            'base_price' => 19.99,
            'has_variants' => '0',
            'sku' => 'TSHIRT-1',
            'stock_quantity' => 10,
        ]);

        $product = Product::where('slug', 't-shirt')->firstOrFail();
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertDatabaseHas('products', [
            'name' => 'T-Shirt',
            'slug' => 't-shirt',
            'base_price' => 19.99,
            'has_variants' => false,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'TSHIRT-1',
            'stock_quantity' => 10,
        ]);
    }

    public function test_admin_can_create_a_product_with_variants(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Hoodie',
            'base_price' => 45,
            'has_variants' => '1',
            'base_sku' => 'HOODIE',
        ]);

        $product = Product::where('slug', 'hoodie')->firstOrFail();
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertTrue($product->has_variants);
        $this->assertEquals('HOODIE', $product->base_sku);
        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_switching_to_standalone_is_blocked_with_multiple_variants(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['has_variants' => true]);
        ProductVariant::factory()->for($product)->count(2)->create();

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'base_price' => $product->base_price,
            'has_variants' => '0',
            'sku' => 'X',
            'stock_quantity' => 1,
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue($product->fresh()->has_variants);
    }

    public function test_variant_sku_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['sku' => 'DUP-1']);
        $other = ProductVariant::factory()->for($product)->create(['sku' => 'DUP-2']);

        $response = $this->actingAs($admin)->put(route('admin.products.variants.update', [$product, $other]), [
            'sku' => 'DUP-1',
            'stock_quantity' => 5,
        ]);

        $response->assertSessionHasErrors('sku');
        $this->assertDatabaseHas('product_variants', ['id' => $other->id, 'sku' => 'DUP-2']);
    }

    public function test_variant_can_be_updated_via_ajax(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'OLD-SKU', 'stock_quantity' => 1]);

        $response = $this->actingAs($admin)->putJson(route('admin.products.variants.update', [$product, $variant]), [
            'sku' => 'NEW-SKU',
            'stock_quantity' => 7,
        ]);

        $response->assertOk()->assertJsonPath('message', 'Variant updated.');
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'sku' => 'NEW-SKU', 'stock_quantity' => 7]);
    }

    public function test_variant_ajax_update_returns_validation_errors_as_json(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['sku' => 'TAKEN']);
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'MINE']);

        $response = $this->actingAs($admin)->putJson(route('admin.products.variants.update', [$product, $variant]), [
            'sku' => 'TAKEN',
            'stock_quantity' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('sku');
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'sku' => 'MINE']);
    }

    public function test_variant_can_be_deleted_via_ajax(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $response = $this->actingAs($admin)->deleteJson(route('admin.products.variants.destroy', [$product, $variant]));

        $response->assertOk()->assertJsonPath('message', 'Variant deleted.');
        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }

    public function test_variant_effective_price_falls_back_to_base_price(): void
    {
        $product = Product::factory()->create(['base_price' => 25]);
        $priced = ProductVariant::factory()->for($product)->create(['price' => 30]);
        $unpriced = ProductVariant::factory()->for($product)->create(['price' => null]);

        $this->assertEquals(30, $priced->effective_price);
        $this->assertEquals(25, $unpriced->effective_price);
    }

    public function test_deleting_product_cascades_to_options_values_and_variants(): void
    {
        $product = Product::factory()->create();
        $option = ProductOption::factory()->for($product)->create();
        $value = ProductOptionValue::factory()->for($option, 'option')->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $variant->optionValues()->attach($value);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_options', ['id' => $option->id]);
        $this->assertDatabaseMissing('product_option_values', ['id' => $value->id]);
        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseMissing('product_variant_option_value', [
            'product_variant_id' => $variant->id,
            'product_option_value_id' => $value->id,
        ]);
    }

    public function test_product_can_be_deleted_via_ajax(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)->deleteJson(route('admin.products.destroy', $product));

        $response->assertOk()->assertJsonPath('message', 'Product deleted.');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_generate_variants_creates_cartesian_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['base_sku' => 'HOODIE', 'base_price' => 45]);

        $size = ProductOption::factory()->for($product)->create(['name' => 'Size']);
        ProductOptionValue::factory()->for($size, 'option')->create(['value' => 'S']);
        ProductOptionValue::factory()->for($size, 'option')->create(['value' => 'M']);

        $color = ProductOption::factory()->for($product)->create(['name' => 'Color']);
        ProductOptionValue::factory()->for($color, 'option')->create(['value' => 'Red']);
        ProductOptionValue::factory()->for($color, 'option')->create(['value' => 'Blue']);

        $this->actingAs($admin)->post(route('admin.products.variants.generate', $product));

        $this->assertDatabaseCount('product_variants', 4);
        $this->assertDatabaseHas('product_variants', ['sku' => 'HOODIE-S-RED', 'price' => 45]);
        $this->assertDatabaseHas('product_variants', ['sku' => 'HOODIE-M-BLUE', 'price' => 45]);

        // Running it again shouldn't create duplicates.
        $this->actingAs($admin)->post(route('admin.products.variants.generate', $product));
        $this->assertDatabaseCount('product_variants', 4);
    }

    public function test_generate_variants_creates_single_default_when_no_options(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.variants.generate', $product));

        $this->assertDatabaseCount('product_variants', 1);
    }

    public function test_generate_variants_copies_product_images_to_each_variant(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['base_sku' => 'HOODIE', 'base_price' => 45]);
        $product->addMedia(UploadedFile::fake()->image('product.jpg'))->toMediaCollection('images');

        $size = ProductOption::factory()->for($product)->create(['name' => 'Size']);
        ProductOptionValue::factory()->for($size, 'option')->create(['value' => 'S']);
        ProductOptionValue::factory()->for($size, 'option')->create(['value' => 'M']);

        $this->actingAs($admin)->post(route('admin.products.variants.generate', $product));

        $product->variants->each(function (ProductVariant $variant) {
            $this->assertCount(1, $variant->fresh()->getMedia('images'));
        });

        // Running it again shouldn't duplicate images on already-existing variants.
        $this->actingAs($admin)->post(route('admin.products.variants.generate', $product));
        $product->variants->each(function (ProductVariant $variant) {
            $this->assertCount(1, $variant->fresh()->getMedia('images'));
        });
    }

    public function test_option_values_can_be_supplied_as_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['has_variants' => true]);

        $this->actingAs($admin)->post(route('admin.products.options.store', $product), [
            'name' => 'Size',
            'values' => ' S ,M,, L ,M',
        ]);

        $option = $product->options()->where('name', 'Size')->firstOrFail();
        $this->assertEquals(['S', 'M', 'L'], $option->values()->orderBy('position')->pluck('value')->all());
    }

    public function test_csv_values_can_be_added_to_an_existing_option(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['has_variants' => true]);
        $option = ProductOption::factory()->for($product)->create();
        ProductOptionValue::factory()->for($option, 'option')->create(['value' => 'S']);

        $this->actingAs($admin)->post(route('admin.products.options.values.store', [$product, $option]), [
            'value' => 'S, M, L',
        ]);

        $this->assertEquals(['S', 'M', 'L'], $option->values()->orderBy('position')->pluck('value')->all());
    }

    public function test_option_value_can_be_added_via_ajax(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['has_variants' => true]);
        $option = ProductOption::factory()->for($product)->create();

        $response = $this->actingAs($admin)->postJson(route('admin.products.options.values.store', [$product, $option]), [
            'value' => 'S, M',
        ]);

        $response->assertOk()->assertJsonPath('message', '2 value(s) added.');
        $this->assertEquals(['S', 'M'], $option->values()->orderBy('position')->pluck('value')->all());
    }

    public function test_option_value_ajax_add_returns_error_json_when_nothing_new(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['has_variants' => true]);
        $option = ProductOption::factory()->for($product)->create();
        ProductOptionValue::factory()->for($option, 'option')->create(['value' => 'S']);

        $response = $this->actingAs($admin)->postJson(route('admin.products.options.values.store', [$product, $option]), [
            'value' => 'S',
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Nothing to add — that value (or all of them) already exist.');
    }

    public function test_option_value_can_be_deleted_via_ajax(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['has_variants' => true]);
        $option = ProductOption::factory()->for($product)->create();
        $value = ProductOptionValue::factory()->for($option, 'option')->create();

        $response = $this->actingAs($admin)->deleteJson(route('admin.products.options.values.destroy', [$product, $option, $value]));

        $response->assertOk()->assertJsonPath('message', 'Value deleted.');
        $this->assertDatabaseMissing('product_option_values', ['id' => $value->id]);
    }

    public function test_option_value_ajax_delete_is_blocked_when_used_by_a_variant(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['has_variants' => true]);
        $option = ProductOption::factory()->for($product)->create();
        $value = ProductOptionValue::factory()->for($option, 'option')->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $variant->optionValues()->attach($value);

        $response = $this->actingAs($admin)->deleteJson(route('admin.products.options.values.destroy', [$product, $option, $value]));

        $response->assertStatus(422)->assertJsonPath('message', 'Delete the variants using this value first.');
        $this->assertDatabaseHas('product_option_values', ['id' => $value->id]);
    }

    public function test_variant_image_can_be_uploaded_directly(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $this->actingAs($admin)->post(route('admin.products.variants.images.store', [$product, $variant]), [
            'images' => [UploadedFile::fake()->image('variant.jpg')],
        ]);

        $this->assertCount(1, $variant->fresh()->getMedia('images'));
        // Uploading directly should also register a copy in the shared media library.
        $this->assertDatabaseHas('media_assets', ['usable_for' => json_encode(['product'])]);
    }

    public function test_variant_image_can_be_picked_from_the_library(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $asset = MediaAsset::create(['name' => 'Library Image', 'usable_for' => ['product']]);
        $asset->addMedia(UploadedFile::fake()->image('library.jpg'))->toMediaCollection('file');

        $this->actingAs($admin)->post(route('admin.products.variants.images.store', [$product, $variant]), [
            'media_asset_ids' => [$asset->id],
        ]);

        $this->assertCount(1, $variant->fresh()->getMedia('images'));
    }

    public function test_variant_image_picked_from_library_is_appended_after_existing_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $variant->addMedia(UploadedFile::fake()->image('existing-one.jpg'))->toMediaCollection('images');
        $variant->addMedia(UploadedFile::fake()->image('existing-two.jpg'))->toMediaCollection('images');
        $existingIds = $variant->fresh()->getMedia('images')->pluck('id')->all();

        // The library asset's own media starts at order_column 1, which
        // previously collided with the variant's own first image.
        $asset = MediaAsset::create(['name' => 'Library Image', 'usable_for' => ['product']]);
        $asset->addMedia(UploadedFile::fake()->image('library.jpg'))->toMediaCollection('file');

        $response = $this->actingAs($admin)->postJson(route('admin.products.variants.images.store', [$product, $variant]), [
            'media_asset_ids' => [$asset->id],
        ]);

        $orderedIds = $variant->fresh()->getMedia('images')->pluck('id')->all();

        $this->assertCount(3, $orderedIds);
        $this->assertEquals($existingIds, array_slice($orderedIds, 0, 2));
        $this->assertEquals(array_slice($orderedIds, 0, 2), array_slice($response->json('images.*.id'), 0, 2));
    }

    public function test_variant_image_can_be_removed(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $variant->addMedia(UploadedFile::fake()->image('variant.jpg'))->toMediaCollection('images');
        $media = $variant->getFirstMedia('images');

        $this->actingAs($admin)->delete(route('admin.products.variants.images.destroy', [$product, $variant, $media]));

        $this->assertCount(0, $variant->fresh()->getMedia('images'));
    }

    public function test_variant_image_can_be_uploaded_via_ajax(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $response = $this->actingAs($admin)->postJson(route('admin.products.variants.images.store', [$product, $variant]), [
            'images' => [UploadedFile::fake()->image('variant.jpg')],
        ]);

        $response->assertOk()->assertJsonPath('message', 'Variant image(s) added.');
        $this->assertCount(1, $response->json('images'));
        $this->assertCount(1, $variant->fresh()->getMedia('images'));
    }

    public function test_variant_image_can_be_removed_via_ajax(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $variant->addMedia(UploadedFile::fake()->image('variant.jpg'))->toMediaCollection('images');
        $media = $variant->getFirstMedia('images');

        $response = $this->actingAs($admin)->deleteJson(route('admin.products.variants.images.destroy', [$product, $variant, $media]));

        $response->assertOk()->assertJsonPath('message', 'Variant image removed.');
        $this->assertCount(0, $response->json('images'));
        $this->assertCount(0, $variant->fresh()->getMedia('images'));
    }

    public function test_product_images_can_be_reordered(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $product->addMedia(UploadedFile::fake()->image('one.jpg'))->toMediaCollection('images');
        $product->addMedia(UploadedFile::fake()->image('two.jpg'))->toMediaCollection('images');

        $ids = $product->fresh()->getMedia('images')->pluck('id')->all();
        $reversed = array_reverse($ids);

        $response = $this->actingAs($admin)->postJson(route('admin.products.images.reorder', $product), [
            'order' => $reversed,
        ]);

        $response->assertOk()->assertJsonPath('message', 'Order updated.');
        $this->assertEquals($reversed, $product->fresh()->getMedia('images')->pluck('id')->all());
    }

    public function test_product_images_reorder_rejects_foreign_media_ids(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $product->addMedia(UploadedFile::fake()->image('one.jpg'))->toMediaCollection('images');

        $otherProduct = Product::factory()->create();
        $otherProduct->addMedia(UploadedFile::fake()->image('two.jpg'))->toMediaCollection('images');
        $foreignId = $otherProduct->fresh()->getFirstMedia('images')->id;

        $response = $this->actingAs($admin)->postJson(route('admin.products.images.reorder', $product), [
            'order' => [$foreignId],
        ]);

        $response->assertStatus(422);
    }

    public function test_variant_images_can_be_reordered(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $variant->addMedia(UploadedFile::fake()->image('one.jpg'))->toMediaCollection('images');
        $variant->addMedia(UploadedFile::fake()->image('two.jpg'))->toMediaCollection('images');

        $ids = $variant->fresh()->getMedia('images')->pluck('id')->all();
        $reversed = array_reverse($ids);

        $response = $this->actingAs($admin)->postJson(route('admin.products.variants.images.reorder', [$product, $variant]), [
            'order' => $reversed,
        ]);

        $response->assertOk()->assertJsonPath('message', 'Order updated.');
        $this->assertEquals($reversed, $variant->fresh()->getMedia('images')->pluck('id')->all());
    }

    public function test_customers_cannot_manage_products(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.products.index'));

        $response->assertForbidden();
    }
}
