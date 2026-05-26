<?php

namespace Tests\Feature\Property;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class ShowPropertyTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_guest_can_view_active_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);

        $response = $this->getJson("/api/properties/{$property->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'title', 'type', 'price', 'images', 'owner']);
    }

    public function test_guest_cannot_view_draft_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);

        $this->getJson("/api/properties/{$property->id}")->assertStatus(403);
    }

    public function test_owner_can_view_own_draft_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)
             ->getJson("/api/properties/{$property->id}")
             ->assertStatus(200);
    }

    public function test_other_user_cannot_view_draft_property(): void
    {
        $owner   = $this->makeProprietaire();
        $other   = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token   = $this->tokenFor($other);

        $this->withToken($token)
             ->getJson("/api/properties/{$property->id}")
             ->assertStatus(403);
    }

    public function test_returns_404_for_nonexistent_property(): void
    {
        $this->getJson('/api/properties/999999')->assertStatus(404);
    }
}
