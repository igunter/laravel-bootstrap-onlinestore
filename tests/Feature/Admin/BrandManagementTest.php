<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_brand(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.brands.store'), [
            'name' => 'Acme Corp',
        ]);

        $response->assertRedirect(route('admin.brands.index'));
        $this->assertDatabaseHas('brands', [
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
    }

    public function test_brand_slug_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Brand::create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);

        $this->actingAs($admin)->post(route('admin.brands.store'), [
            'name' => 'Acme Corp',
        ]);

        $this->assertDatabaseCount('brands', 2);
        $this->assertDatabaseHas('brands', ['slug' => 'acme-corp-1']);
    }

    public function test_admin_views_render(): void
    {
        $admin = User::factory()->admin()->create();
        $brand = Brand::create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);

        $this->actingAs($admin)->get(route('admin.brands.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.brands.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.brands.edit', $brand))->assertOk();
    }

    public function test_brand_can_be_deleted_via_ajax(): void
    {
        $admin = User::factory()->admin()->create();
        $brand = Brand::create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);

        $response = $this->actingAs($admin)->deleteJson(route('admin.brands.destroy', $brand));

        $response->assertOk()->assertJsonPath('message', 'Brand deleted.');
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_customers_cannot_manage_brands(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.brands.index'));

        $response->assertForbidden();
    }
}
