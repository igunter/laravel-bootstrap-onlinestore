<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_root_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Electronics',
            'description' => 'Electronic goods',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
        ]);
    }

    public function test_admin_can_create_a_child_category(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Laptops',
            'parent_id' => $parent->id,
        ]);

        $response->assertRedirect(route('admin.categories.index'));

        $child = Category::where('slug', 'electronics-laptops')->firstOrFail();
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertTrue($child->isDescendantOf($parent->refresh()));
    }

    public function test_category_slug_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Electronics',
        ]);

        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseHas('categories', ['slug' => 'electronics-1']);
    }

    public function test_admin_views_render(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
        Category::create(['name' => 'Laptops', 'slug' => 'laptops', 'parent_id' => $parent->id]);

        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.categories.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.categories.edit', $parent))->assertOk();
    }

    public function test_customers_cannot_manage_categories(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.categories.index'));

        $response->assertForbidden();
    }
}
