<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_avatar_returns_relative_paths(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension not installed.');
        }

        Storage::fake('public');

        $file    = UploadedFile::fake()->image('test.jpg', 800, 800);
        $service = new ImageService();
        $result  = $service->uploadAvatar($file, 1);

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('thumb_path', $result);
        $this->assertStringStartsWith('avatars/1/', $result['path']);
        $this->assertStringStartsWith('avatars/1/', $result['thumb_path']);

        Storage::disk('public')->assertExists($result['path']);
        Storage::disk('public')->assertExists($result['thumb_path']);
    }

    public function test_delete_avatar_removes_files_from_storage(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'avatar_path'       => 'avatars/1/old_profile.webp',
            'avatar_thumb_path' => 'avatars/1/old_thumb.webp',
        ]);

        Storage::disk('public')->put('avatars/1/old_profile.webp', 'fake');
        Storage::disk('public')->put('avatars/1/old_thumb.webp', 'fake');

        $service = new ImageService();
        $service->deleteAvatar($user);

        Storage::disk('public')->assertMissing('avatars/1/old_profile.webp');
        Storage::disk('public')->assertMissing('avatars/1/old_thumb.webp');
    }
}
