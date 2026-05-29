<?php

namespace Tests\Feature\Messaging;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesConversations;
use Tests\Traits\CreatesProperties;

class ConversationTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesConversations;

    public function test_locataire_peut_demarrer_une_conversation(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $response = $this->withToken($token)->postJson(
            "/api/conversations/properties/{$property->id}",
            ['initial_message' => 'Bonjour, ce bien est-il toujours disponible ?']
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('conversations', [
            'property_id'  => $property->id,
            'initiated_by' => $tenant->id,
        ]);
        $this->assertDatabaseCount('conversation_participants', 2);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_conversation_existante_est_reutilisee(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)->postJson(
            "/api/conversations/properties/{$property->id}",
            ['initial_message' => 'Premier message.']
        )->assertStatus(201);

        $this->withToken($token)->postJson(
            "/api/conversations/properties/{$property->id}",
            ['initial_message' => 'Deuxième message.']
        )->assertStatus(201);

        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('messages', 2);
    }

    public function test_proprietaire_ne_peut_pas_demarrer_conversation(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->postJson(
            "/api/conversations/properties/{$property->id}",
            ['initial_message' => 'Bonjour depuis le proprio.']
        )->assertStatus(403);
    }

    public function test_impossible_conversation_sur_bien_non_approuve(): void
    {
        $owner    = $this->makeProprietaire();
        $property = \App\Models\Property::factory()->for($owner, 'owner')->pending()->create();
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)->postJson(
            "/api/conversations/properties/{$property->id}",
            ['initial_message' => 'Ce bien est-il disponible ?']
        )->assertStatus(422);
    }

    public function test_locataire_voit_ses_conversations(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();

        $this->createConversation($tenantA);
        $this->createConversation($tenantA);
        $this->createConversation($tenantA);
        $this->createConversation($tenantB);
        $this->createConversation($tenantB);

        $token    = $this->tokenFor($tenantA);
        $response = $this->withToken($token)->getJson('/api/conversations');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_proprietaire_voit_conversations_sur_ses_biens(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);

        $this->createConversation(null, $property);
        $this->createConversation(null, $property);

        // Conversation sur un autre bien (propriétaire différent)
        $this->createConversation();

        $token    = $this->tokenFor($owner);
        $response = $this->withToken($token)->getJson('/api/conversations');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_liste_conversations_triee_par_dernier_message(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);

        $conv1 = $this->createConversation($tenant, null, [
            'last_message_at' => now()->subHour(),
        ]);
        $conv2 = $this->createConversation($tenant, null, [
            'last_message_at' => now()->subMinutes(5),
        ]);

        $response = $this->withToken($token)->getJson('/api/conversations');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($conv2->id, $data[0]['id']);
    }

    public function test_conversations_archivees_exclues_par_defaut(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);

        $conv1 = $this->createConversation($tenant);
        $conv2 = $this->createConversation($tenant);
        $conv3 = $this->createConversation($tenant);

        // Archiver conv3 pour le tenant
        $conv3->participants()->updateExistingPivot($tenant->id, ['is_archived' => true]);

        $response = $this->withToken($token)->getJson('/api/conversations');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filtre_archived_true_retourne_archivees(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);

        $conv1 = $this->createConversation($tenant);
        $conv2 = $this->createConversation($tenant);
        $conv3 = $this->createConversation($tenant);

        $conv3->participants()->updateExistingPivot($tenant->id, ['is_archived' => true]);

        $response = $this->withToken($token)->getJson('/api/conversations?archived=true');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($conv3->id, $response->json('data.0.id'));
    }

    public function test_detail_conversation_marque_comme_lu(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $this->setUnreadCount($conv, $tenant, 3);

        $this->withToken($token)->getJson("/api/conversations/{$conv->id}")->assertStatus(200);

        $pivot = $conv->fresh()->participants()->where('user_id', $tenant->id)->first()->pivot;
        $this->assertEquals(0, $pivot->unread_count);
    }

    public function test_non_participant_ne_peut_pas_voir_conversation(): void
    {
        $tiers = $this->makeTenant();
        $token = $this->tokenFor($tiers);
        $conv  = $this->createConversation();

        $this->withToken($token)->getJson("/api/conversations/{$conv->id}")->assertStatus(403);
    }

    public function test_locataire_peut_archiver_conversation(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);
        $conv     = $this->createConversation($tenant, $property);

        $this->withToken($token)
             ->postJson("/api/conversations/{$conv->id}/archive")
             ->assertStatus(200);

        $tenantPivot = $conv->fresh()->participants()->where('user_id', $tenant->id)->first()->pivot;
        $ownerPivot  = $conv->fresh()->participants()->where('user_id', $owner->id)->first()->pivot;

        $this->assertTrue((bool) $tenantPivot->is_archived);
        $this->assertFalse((bool) $ownerPivot->is_archived);
    }

    public function test_non_authentifie_ne_peut_pas_lister_conversations(): void
    {
        $this->getJson('/api/conversations')->assertStatus(401);
    }

    protected function makeTenant(array $attrs = []): \App\Models\User
    {
        return User::factory()->create(array_merge([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $attrs));
    }
}
