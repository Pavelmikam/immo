<?php

namespace Tests\Feature\Property;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class UpdatePropertyTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_owner_can_update_draft_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($owner);

        $response = $this->withToken($token)->putJson("/api/properties/{$property->id}", [
            'price' => 200000,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(200000, $response->json('price'));
    }

    public function test_owner_can_update_rejected_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'rejected', 'rejection_reason' => 'Mauvaise qualité.']);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->putJson("/api/properties/{$property->id}", ['price' => 99000])
             ->assertStatus(200);
    }

    public function test_owner_cannot_update_active_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->putJson("/api/properties/{$property->id}", ['price' => 99000])
             ->assertStatus(403);
    }

    public function test_other_user_cannot_update_property(): void
    {
        $owner    = $this->makeProprietaire();
        $other    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($other);

        $this->withToken($token)->putJson("/api/properties/{$property->id}", ['price' => 99000])
             ->assertStatus(403);
    }

    public function test_returns_422_for_invalid_price(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->putJson("/api/properties/{$property->id}", ['price' => -100])
             ->assertStatus(422)
             ->assertJsonStructure(['errors' => ['price']]);
    }
}
