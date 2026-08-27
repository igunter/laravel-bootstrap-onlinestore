<?php

namespace Tests\Feature;

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
    public function test_a_guest_can_trigger_a_fresh_migrate_and_seed(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:fresh', ['--force' => true, '--seed' => true]);

        $response = $this->post(route('demo-data.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_a_logged_in_customer_can_also_trigger_it(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:fresh', ['--force' => true, '--seed' => true]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('demo-data.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
