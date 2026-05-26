<?php

namespace Tests\Feature\Property;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class PropertyImageTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_owner_can_upload_image_to_draft_property(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension not installed.');
        }

        Storage::fake('media');

        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($owner);
        $file     = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->withToken($token)
                         ->postJson("/api/properties/{$property->id}/images", ['image' => $file]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'original_url', 'optimized_url', 'thumbnail_url', 'is_primary']);

        $this->assertTrue($response->json('is_primary'));
        $this->assertDatabaseHas('property_images', ['property_id' => $property->id, 'is_primary' => true]);
    }

    public function test_second_image_is_not_primary(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension not installed.');
        }

        Storage::fake('media');

        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $this->attachFakeImages($property, 1);
        $token    = $this->tokenFor($owner);
        $file     = UploadedFile::fake()->image('photo2.jpg', 800, 600);

        $response = $this->withToken($token)
                         ->postJson("/api/properties/{$property->id}/images", ['image' => $file]);

        $response->assertStatus(201);
        $this->assertFalse($response->json('is_primary'));
    }

    public function test_fails_if_file_exceeds_max_size(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension not installed.');
        }

        Storage::fake('media');

        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($owner);
        $file     = UploadedFile::fake()->image('big.jpg')->size(6000);

        $this->withToken($token)
             ->postJson("/api/properties/{$property->id}/images", ['image' => $file])
             ->assertStatus(422)
             ->assertJsonStructure(['errors' => ['image']]);
    }

    public function test_fails_if_file_is_not_an_image(): void
    {
        Storage::fake('media');

        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($owner);
        $file     = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->withToken($token)
             ->postJson("/api/properties/{$property->id}/images", ['image' => $file])
             ->assertStatus(422);
    }

    public function test_owner_cannot_upload_image_to_active_property(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension not installed.');
        }

        Storage::fake('media');

        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);
        $token    = $this->tokenFor($owner);
        $file     = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->withToken($token)
             ->postJson("/api/properties/{$property->id}/images", ['image' => $file])
             ->assertStatus(403);
    }

    public function test_owner_can_delete_image(): void
    {
        Storage::fake('media');

        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $this->attachFakeImages($property, 2);
        $token    = $this->tokenFor($owner);

        $image = $property->images()->orderBy('order')->first();

        $this->withToken($token)
             ->deleteJson("/api/properties/{$property->id}/images/{$image->id}")
             ->assertStatus(204);

        $this->assertSoftDeleted('property_images', ['id' => $image->id]);
    }
}
