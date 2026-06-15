<?php

namespace Tests\Feature\Property;

use App\Models\User;
use App\Notifications\NewPropertyPendingNotification;
use App\Notifications\PropertyApprovedNotification;
use App\Notifications\PropertyRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class PropertyModerationFlowTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    // ─── Soumission ────────────────────────────────────────────────────────────

    public function test_soumission_passe_statut_a_pending(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $this->attachFakeImages($property);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)
             ->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(200)
             ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('properties', [
            'id'     => $property->id,
            'status' => 'pending',
        ]);
    }

    public function test_soumission_notifie_tous_les_admins(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $this->attachFakeImages($property);
        $token    = $this->tokenFor($owner);

        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $this->withToken($token)
             ->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(200);

        Notification::assertSentTo($admin1, NewPropertyPendingNotification::class);
        Notification::assertSentTo($admin2, NewPropertyPendingNotification::class);
    }

    public function test_soumission_ne_notifie_pas_les_admins_suspendus(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $this->attachFakeImages($property);
        $token    = $this->tokenFor($owner);

        $activeAdmin    = User::factory()->admin()->create(['is_active' => true]);
        $suspendedAdmin = User::factory()->admin()->create(['is_active' => false]);

        $this->withToken($token)
             ->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(200);

        Notification::assertSentTo($activeAdmin, NewPropertyPendingNotification::class);
        Notification::assertNotSentTo($suspendedAdmin, NewPropertyPendingNotification::class);
    }

    public function test_resoumission_dune_annonce_rejetee_repasse_en_pending(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, [
            'status'           => 'rejected',
            'rejection_reason' => 'Photos insuffisantes.',
        ]);
        $this->attachFakeImages($property);
        $token = $this->tokenFor($owner);

        $admin = User::factory()->admin()->create();

        $this->withToken($token)
             ->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(200)
             ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('properties', [
            'id'               => $property->id,
            'status'           => 'pending',
            'rejection_reason' => null,
        ]);

        Notification::assertSentTo($admin, NewPropertyPendingNotification::class);
    }

    public function test_proprietaire_ne_peut_pas_soumettre_une_annonce_deja_active(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'active']);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)
             ->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(403);
    }

    public function test_proprietaire_ne_peut_pas_soumettre_annonce_dun_autre(): void
    {
        $owner   = $this->makeProprietaire();
        $other   = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $token   = $this->tokenFor($other);

        $this->withToken($token)
             ->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(403);
    }

    // ─── Approbation ───────────────────────────────────────────────────────────

    public function test_approbation_passe_statut_a_active(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $admin    = User::factory()->admin()->create();
        $token    = $this->tokenFor($admin);

        $this->withToken($token)
             ->postJson("/api/admin/properties/{$property->id}/moderate", ['action' => 'approve'])
             ->assertStatus(200)
             ->assertJsonPath('status', 'active');
    }

    public function test_approbation_notifie_le_proprietaire(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $admin    = User::factory()->admin()->create();
        $token    = $this->tokenFor($admin);

        $this->withToken($token)
             ->postJson("/api/admin/properties/{$property->id}/moderate", ['action' => 'approve'])
             ->assertStatus(200);

        Notification::assertSentTo($owner, PropertyApprovedNotification::class);
    }

    // ─── Rejet ─────────────────────────────────────────────────────────────────

    public function test_rejet_passe_statut_a_rejected_avec_motif(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $admin    = User::factory()->admin()->create();
        $token    = $this->tokenFor($admin);

        $this->withToken($token)
             ->postJson("/api/admin/properties/{$property->id}/moderate", [
                 'action'           => 'reject',
                 'rejection_reason' => 'Photos de mauvaise qualité.',
             ])
             ->assertStatus(200)
             ->assertJsonPath('status', 'rejected');

        $this->assertDatabaseHas('properties', [
            'id'               => $property->id,
            'status'           => 'rejected',
            'rejection_reason' => 'Photos de mauvaise qualité.',
        ]);
    }

    public function test_rejet_notifie_le_proprietaire(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $admin    = User::factory()->admin()->create();
        $token    = $this->tokenFor($admin);

        $this->withToken($token)
             ->postJson("/api/admin/properties/{$property->id}/moderate", [
                 'action'           => 'reject',
                 'rejection_reason' => 'Description insuffisante.',
             ])
             ->assertStatus(200);

        Notification::assertSentTo($owner, PropertyRejectedNotification::class);
    }

    // ─── Vitrine publique ──────────────────────────────────────────────────────

    public function test_annonce_active_apparait_dans_la_vitrine(): void
    {
        $owner = $this->makeProprietaire();
        $this->makeProperty($owner, ['status' => 'active']);

        $response = $this->getJson('/api/properties');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_annonce_pending_ninclut_pas_dans_la_vitrine(): void
    {
        $owner = $this->makeProprietaire();
        $this->makeProperty($owner, ['status' => 'pending']);

        $response = $this->getJson('/api/properties');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_annonce_rejected_nexclut_pas_la_vitrine(): void
    {
        $owner = $this->makeProprietaire();
        $this->makeProperty($owner, ['status' => 'rejected']);

        $response = $this->getJson('/api/properties');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_annonce_draft_nexclut_pas_la_vitrine(): void
    {
        $owner = $this->makeProprietaire();
        $this->makeProperty($owner, ['status' => 'draft']);

        $response = $this->getJson('/api/properties');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    // ─── Flux complet ──────────────────────────────────────────────────────────

    public function test_flux_complet_creation_soumission_approbation_vitrine(): void
    {
        Notification::fake();

        $owner = $this->makeProprietaire();
        $admin = User::factory()->admin()->create();

        // 1. Propriétaire crée en brouillon
        $property = $this->makeProperty($owner, ['status' => 'draft']);
        $this->attachFakeImages($property);

        // Vitrine vide
        $this->assertCount(0, $this->getJson('/api/properties')->json('data'));

        // 2. Propriétaire soumet → pending
        $this->withToken($this->tokenFor($owner))
             ->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(200)
             ->assertJsonPath('status', 'pending');

        // Admin notifié
        Notification::assertSentTo($admin, NewPropertyPendingNotification::class);

        // Vitrine toujours vide (pending n'est pas visible)
        $this->assertCount(0, $this->getJson('/api/properties')->json('data'));

        // 3. Admin approuve → active
        $this->withToken($this->tokenFor($admin))
             ->postJson("/api/admin/properties/{$property->id}/moderate", ['action' => 'approve'])
             ->assertStatus(200)
             ->assertJsonPath('status', 'active');

        // Propriétaire notifié
        Notification::assertSentTo($owner, PropertyApprovedNotification::class);

        // Vitrine contient l'annonce
        $this->assertCount(1, $this->getJson('/api/properties')->json('data'));
    }

    public function test_flux_rejet_puis_resoumission_puis_approbation(): void
    {
        Notification::fake();

        $owner = $this->makeProprietaire();
        $admin = User::factory()->admin()->create();

        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $this->attachFakeImages($property);
        $ownerToken = $this->tokenFor($owner);
        $adminToken = $this->tokenFor($admin);

        // Admin rejette
        $this->withToken($adminToken)
             ->postJson("/api/admin/properties/{$property->id}/moderate", [
                 'action'           => 'reject',
                 'rejection_reason' => 'Description trop courte.',
             ])
             ->assertStatus(200)
             ->assertJsonPath('status', 'rejected');

        Notification::assertSentTo($owner, PropertyRejectedNotification::class);
        $this->assertCount(0, $this->getJson('/api/properties')->json('data'));

        // Propriétaire resoumet après correction
        $this->withToken($ownerToken)
             ->postJson("/api/properties/{$property->id}/submit")
             ->assertStatus(200)
             ->assertJsonPath('status', 'pending');

        Notification::assertSentTo($admin, NewPropertyPendingNotification::class);

        // Admin approuve
        $this->withToken($adminToken)
             ->postJson("/api/admin/properties/{$property->id}/moderate", ['action' => 'approve'])
             ->assertStatus(200)
             ->assertJsonPath('status', 'active');

        $this->assertCount(1, $this->getJson('/api/properties')->json('data'));
    }
}
