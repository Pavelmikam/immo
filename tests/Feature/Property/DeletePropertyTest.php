<?php

namespace Tests\Feature\Property;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class DeletePropertyTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_owner_can_delete_draft_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->deleteJson("/api/properties/{$property->id}")
             ->assertStatus(204);

        $this->assertDatabaseMissing('properties', ['id' => $property->id, 'deleted_at' => null]);
    }

    public function test_owner_cannot_delete_active_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->deleteJson("/api/properties/{$property->id}")
             ->assertStatus(403);
    }

    public function test_other_user_cannot_delete_property(): void
    {
        $owner    = $this->makeProprietaire();
        $other    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token    = $this->tokenFor($other);

        $this->withToken($token)->deleteJson("/api/properties/{$property->id}")
             ->assertStatus(403);
    }

    public function test_admin_can_delete_any_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $admin    = User::factory()->admin()->create();
        $token    = $this->tokenFor($admin);

        $this->withToken($token)->deleteJson("/api/properties/{$property->id}")
             ->assertStatus(204);
    }

    public function test_unauthenticated_cannot_delete_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);

        $this->deleteJson("/api/properties/{$property->id}")->assertStatus(401);
    }
}
