<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class ModeratePropertyTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_admin_can_list_all_properties(): void
    {
        $owner = $this->makeProprietaire();
        $this->makeProperty($owner, ['status' => 'draft']);
        $this->makeProperty($owner, ['status' => 'pending']);
        $this->makeActiveProperty($owner);

        $admin = User::factory()->admin()->create();
        $token = $this->tokenFor($admin);

        $response = $this->withToken($token)->getJson('/api/admin/properties');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_approve_pending_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $admin    = User::factory()->admin()->create();
        $token    = $this->tokenFor($admin);

        $response = $this->withToken($token)
                         ->postJson("/api/admin/properties/{$property->id}/moderate", [
                             'action' => 'approve',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'active');
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'status' => 'active']);
    }

    public function test_admin_can_reject_pending_property(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $admin    = User::factory()->admin()->create();
        $token    = $this->tokenFor($admin);

        $response = $this->withToken($token)
                         ->postJson("/api/admin/properties/{$property->id}/moderate", [
                             'action' => 'reject',
                             'rejection_reason' => 'Photos de mauvaise qualité, veuillez les remplacer.',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'rejected');
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'status' => 'rejected']);
    }

    public function test_reject_requires_reason(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $admin    = User::factory()->admin()->create();
        $token    = $this->tokenFor($admin);

        $this->withToken($token)
             ->postJson("/api/admin/properties/{$property->id}/moderate", [
                 'action' => 'reject',
             ])
             ->assertStatus(422)
             ->assertJsonStructure(['errors' => ['rejection_reason']]);
    }

    public function test_non_admin_cannot_moderate(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)
             ->postJson("/api/admin/properties/{$property->id}/moderate", ['action' => 'approve'])
             ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_admin(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);

        $this->postJson("/api/admin/properties/{$property->id}/moderate", ['action' => 'approve'])
             ->assertStatus(401);
    }

    public function test_admin_can_filter_properties_by_status(): void
    {
        $owner = $this->makeProprietaire();
        $this->makeProperty($owner, ['status' => 'draft']);
        $this->makeProperty($owner, ['status' => 'pending']);
        $this->makeProperty($owner, ['status' => 'pending']);

        $admin = User::factory()->admin()->create();
        $token = $this->tokenFor($admin);

        $response = $this->withToken($token)->getJson('/api/admin/properties?status=pending');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }
}
