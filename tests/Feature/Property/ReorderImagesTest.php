<?php

namespace Tests\Feature\Property;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class ReorderImagesTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_owner_can_reorder_images(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $this->attachFakeImages($property, 3);
        $token    = $this->tokenFor($owner);

        $images   = $property->images()->orderBy('order')->get();
        $reversed = [$images[2]->id, $images[1]->id, $images[0]->id];

        $response = $this->withToken($token)
                         ->putJson("/api/properties/{$property->id}/images/reorder", ['order' => $reversed]);

        $response->assertStatus(200);

        // First in new order is now primary
        $this->assertDatabaseHas('property_images', ['id' => $reversed[0], 'is_primary' => true]);
        $this->assertDatabaseHas('property_images', ['id' => $reversed[1], 'is_primary' => false]);
    }

    public function test_other_user_cannot_reorder_images(): void
    {
        $owner    = $this->makeProprietaire();
        $other    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $this->attachFakeImages($property, 2);
        $token    = $this->tokenFor($other);

        $images = $property->images()->pluck('id')->toArray();

        $this->withToken($token)
             ->putJson("/api/properties/{$property->id}/images/reorder", ['order' => $images])
             ->assertStatus(403);
    }

    public function test_returns_422_if_order_is_empty(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)
             ->putJson("/api/properties/{$property->id}/images/reorder", ['order' => []])
             ->assertStatus(422);
    }
}
