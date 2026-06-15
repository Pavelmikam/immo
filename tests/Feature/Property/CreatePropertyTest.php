<?php

namespace Tests\Feature\Property;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class CreatePropertyTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    private function validPayload(): array
    {
        return [
            'title'            => 'Bel appartement au centre de Yaoundé',
            'description'      => str_repeat('Une belle description du logement. ', 5),
            'type'             => 'apartment',
            'transaction_type' => 'rent',
            'price'            => 150000,
            'surface'          => 75,
            'rooms'            => 3,
            'city'             => 'Yaoundé',
        ];
    }

    public function test_proprietaire_can_create_property(): void
    {
        $owner = $this->makeProprietaire();
        $token = $this->tokenFor($owner);

        $response = $this->withToken($token)->postJson('/api/properties', $this->validPayload());

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'title', 'status']);
        $this->assertEquals('draft', $response->json('status'));
        $this->assertDatabaseHas('properties', ['user_id' => $owner->id, 'status' => 'draft']);
    }

    public function test_locataire_cannot_create_property(): void
    {
        $user  = User::factory()->locataire()->create();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/properties', $this->validPayload())
             ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_create_property(): void
    {
        $this->postJson('/api/properties', $this->validPayload())->assertStatus(401);
    }

    public function test_unverified_proprietaire_can_create_draft(): void
    {
        // Design: unverified proprietaires may create drafts; email verification is only
        // required at submit time (route middleware: verified.api on POST /submit).
        $owner = User::factory()->proprietaire()->unverified()->create();
        $token = $this->tokenFor($owner);

        $response = $this->withToken($token)->postJson('/api/properties', $this->validPayload());

        $response->assertStatus(201);
        $this->assertEquals('draft', $response->json('status'));
    }

    public function test_unverified_proprietaire_cannot_submit_property(): void
    {
        $owner    = User::factory()->proprietaire()->unverified()->create();
        $token    = $this->tokenFor($owner);
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $this->attachFakeImages($property);

        $this->withToken($token)
             ->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(403)
             ->assertJsonFragment(['code' => 'EMAIL_NOT_VERIFIED']);
    }

    public function test_returns_422_when_required_fields_missing(): void
    {
        $owner = $this->makeProprietaire();
        $token = $this->tokenFor($owner);

        $this->withToken($token)->postJson('/api/properties', [])
             ->assertStatus(422)
             ->assertJsonStructure(['errors' => ['title', 'description', 'type', 'transaction_type', 'price', 'city']]);
    }

    public function test_returns_422_when_invalid_type(): void
    {
        $owner   = $this->makeProprietaire();
        $token   = $this->tokenFor($owner);
        $payload = array_merge($this->validPayload(), ['type' => 'invalid-type']);

        $this->withToken($token)->postJson('/api/properties', $payload)
             ->assertStatus(422)
             ->assertJsonStructure(['errors' => ['type']]);
    }

    public function test_returns_422_when_description_too_short(): void
    {
        $owner   = $this->makeProprietaire();
        $token   = $this->tokenFor($owner);
        $payload = array_merge($this->validPayload(), ['description' => 'Trop court.']);

        $this->withToken($token)->postJson('/api/properties', $payload)
             ->assertStatus(422)
             ->assertJsonStructure(['errors' => ['description']]);
    }
}
