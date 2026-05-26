<?php

namespace Tests\Feature\Property;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class SubmitPropertyTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_owner_can_submit_draft_property_with_images(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $this->attachFakeImages($property);
        $token = $this->tokenFor($owner);

        $response = $this->withToken($token)->postJson("/api/properties/{$property->id}/submit");

        $response->assertStatus(200);
        $this->assertEquals('pending', $response->json('status'));
    }

    public function test_owner_cannot_submit_draft_without_images(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(403);
    }

    public function test_owner_can_resubmit_rejected_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'rejected']);
        $this->attachFakeImages($property);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(200)
             ->assertJsonPath('status', 'pending');
    }

    public function test_owner_cannot_submit_pending_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $this->attachFakeImages($property);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(403);
    }
}
