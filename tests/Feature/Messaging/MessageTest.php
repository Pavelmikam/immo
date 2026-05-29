<?php

namespace Tests\Feature\Messaging;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesConversations;
use Tests\Traits\CreatesProperties;

class MessageTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesConversations;

    public function test_participant_peut_envoyer_message(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $response = $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            ['body' => 'Bonjour !']
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'sender_id'       => $tenant->id,
            'body'            => 'Bonjour !',
        ]);
    }

    public function test_envoi_message_met_a_jour_snapshot_conversation(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            ['body' => 'Test du snapshot de conversation.']
        )->assertStatus(201);

        $fresh = $conv->fresh();
        $this->assertNotNull($fresh->last_message_at);
        $this->assertStringContainsString('Test du snapshot', $fresh->last_message_preview);
    }

    public function test_envoi_message_incremente_unread_pour_autres(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);
        $conv     = $this->createConversation($tenant, $property);

        $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            ['body' => 'Hello owner!']
        )->assertStatus(201);

        $ownerPivot  = $conv->fresh()->participants()->where('user_id', $owner->id)->first()->pivot;
        $tenantPivot = $conv->fresh()->participants()->where('user_id', $tenant->id)->first()->pivot;

        $this->assertEquals(1, $ownerPivot->unread_count);
        $this->assertEquals(0, $tenantPivot->unread_count);
    }

    public function test_non_participant_ne_peut_pas_envoyer(): void
    {
        $tiers = $this->makeTenant();
        $token = $this->tokenFor($tiers);
        $conv  = $this->createConversation();

        $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            ['body' => 'Tentative !']
        )->assertStatus(403);
    }

    public function test_message_vide_refuse(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            ['body' => '']
        )->assertStatus(422)->assertJsonValidationErrors(['body']);
    }

    public function test_message_trop_long_refuse(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            ['body' => str_repeat('a', 2001)]
        )->assertStatus(422)->assertJsonValidationErrors(['body']);
    }

    public function test_les_messages_ne_peuvent_pas_etre_modifies(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);
        $msg    = Message::factory()->create([
            'conversation_id' => $conv->id,
            'sender_id'       => $tenant->id,
        ]);

        $this->withToken($token)
             ->putJson("/api/conversations/{$conv->id}/messages/{$msg->id}", ['body' => 'Modifié'])
             ->assertStatus(404);
    }

    public function test_les_messages_ne_peuvent_pas_etre_supprimes(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);
        $msg    = Message::factory()->create([
            'conversation_id' => $conv->id,
            'sender_id'       => $tenant->id,
        ]);

        $this->withToken($token)
             ->deleteJson("/api/conversations/{$conv->id}/messages/{$msg->id}")
             ->assertStatus(404);
    }

    public function test_messages_pagines_par_30(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        for ($i = 0; $i < 35; $i++) {
            Message::factory()->create([
                'conversation_id' => $conv->id,
                'sender_id'       => $tenant->id,
            ]);
        }

        $response = $this->withToken($token)->getJson("/api/conversations/{$conv->id}/messages");

        $response->assertStatus(200);
        $this->assertCount(30, $response->json('data'));
        $this->assertEquals(35, $response->json('meta.total'));
    }

    public function test_participant_peut_envoyer_message_avec_piece_jointe(): void
    {
        Storage::fake('media');

        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $response = $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            [
                'body'          => 'Voici mon document.',
                'attachments'   => [
                    UploadedFile::fake()->create('document.pdf', 200, 'application/pdf'),
                ],
            ]
        );

        $response->assertStatus(201);
        $this->assertDatabaseCount('message_attachments', 1);
        $this->assertDatabaseHas('message_attachments', [
            'original_name'   => 'document.pdf',
            'attachment_type' => 'document',
        ]);
    }

    public function test_plus_de_3_pieces_jointes_refusees(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            [
                'body'        => 'Trop de fichiers.',
                'attachments' => [
                    UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->create('c.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->create('d.pdf', 100, 'application/pdf'),
                ],
            ]
        )->assertStatus(422)->assertJsonValidationErrors(['attachments']);
    }

    public function test_resource_message_contient_is_mine(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $conv     = $this->createConversation($tenant, $property);

        Message::factory()->create([
            'conversation_id' => $conv->id,
            'sender_id'       => $tenant->id,
        ]);

        // Vue du tenant
        $tenantResponse = $this->withToken($this->tokenFor($tenant))
            ->getJson("/api/conversations/{$conv->id}/messages");
        $this->assertTrue($tenantResponse->json('data.0.is_mine'));

        // Vue du owner
        $ownerResponse = $this->withToken($this->tokenFor($owner))
            ->getJson("/api/conversations/{$conv->id}/messages");
        $this->assertFalse($ownerResponse->json('data.0.is_mine'));
    }

    protected function makeTenant(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $attrs));
    }
}
