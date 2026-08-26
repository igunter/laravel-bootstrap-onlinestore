<?php

namespace Tests\Feature\Admin;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAssetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_asset_can_be_deleted_via_ajax(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $asset = MediaAsset::create(['name' => 'Sample', 'usable_for' => ['product']]);
        $asset->addMedia(UploadedFile::fake()->image('sample.jpg'))->toMediaCollection('file');

        $response = $this->actingAs($admin)->deleteJson(route('admin.media.destroy', $asset));

        $response->assertOk()->assertJsonPath('message', 'Media item deleted.');
        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
    }
}
