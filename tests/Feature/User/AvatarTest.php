<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_valid_avatar_returns_urls(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension not installed.');
        }

        Storage::fake('public');

        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;
        $file  = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->withToken($token)->postJson('/api/user/avatar', ['avatar' => $file]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('avatar_url'));

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_thumb_path);
    }

    public function test_fails_if_file_exceeds_max_size(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension not installed.');
        }

        Storage::fake('public');

        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;
        $file  = UploadedFile::fake()->image('big.jpg')->size(3000);

        $this->withToken($token)
             ->postJson('/api/user/avatar', ['avatar' => $file])
             ->assertStatus(422)
             ->assertJsonStructure(['errors' => ['avatar']]);
    }

    public function test_fails_if_file_is_not_an_image(): void
    {
        Storage::fake('public');

        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;
        $file  = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->withToken($token)
             ->postJson('/api/user/avatar', ['avatar' => $file])
             ->assertStatus(422);
    }

    public function test_deletes_old_avatar_on_new_upload(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension not installed.');
        }

        Storage::fake('public');

        $user = User::factory()->create([
            'avatar_path'       => 'avatars/1/old_profile.webp',
            'avatar_thumb_path' => 'avatars/1/old_thumb.webp',
        ]);
        Storage::disk('public')->put('avatars/1/old_profile.webp', 'fake');
        Storage::disk('public')->put('avatars/1/old_thumb.webp', 'fake');

        $token = $user->createToken('api-token')->plainTextToken;
        $file  = UploadedFile::fake()->image('new.jpg', 200, 200);

        $this->withToken($token)->postJson('/api/user/avatar', ['avatar' => $file])->assertStatus(200);

        Storage::disk('public')->assertMissing('avatars/1/old_profile.webp');
        Storage::disk('public')->assertMissing('avatars/1/old_thumb.webp');
    }
}
