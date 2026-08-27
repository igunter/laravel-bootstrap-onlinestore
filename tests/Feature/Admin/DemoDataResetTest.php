<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoDataResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Doesn't actually run migrate:fresh — SQLite can't VACUUM (part of what
     * migrate:fresh does) inside the DB transaction RefreshDatabase wraps
     * every test in, which is a PHPUnit-only artifact (a real request isn't
     * wrapped in a transaction). Asserting the controller invokes the right
     * Artisan command is what's actually under test here.
     */
    public function test_admin_triggers_a_fresh_migrate_and_seed(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:fresh', ['--force' => true, '--seed' => true]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.demo-data.reset'));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');
    }

    public function test_customer_cannot_reset_demo_data(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->post(route('admin.demo-data.reset'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('admin.demo-data.reset'))->assertRedirect(route('login'));
    }
}
