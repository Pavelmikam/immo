<?php

namespace Tests\Feature\Messaging;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesConversations;
use Tests\Traits\CreatesProperties;

class MessagePollingTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesConversations;

    public function test_polling_retourne_messages_depuis_id_donne(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $messages = [];
        for ($i = 0; $i < 5; $i++) {
            $messages[] = Message::factory()->create([
                'conversation_id' => $conv->id,
                'sender_id'       => $tenant->id,
            ]);
        }

        $sinceId = $messages[2]->id; // msg3

        $response = $this->withToken($token)
            ->getJson("/api/conversations/{$conv->id}/messages/since?since_id={$sinceId}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data')); // msg4 et msg5
    }

    public function test_polling_retourne_tableau_vide_si_pas_de_nouveaux(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $lastMsg = Message::factory()->create([
            'conversation_id' => $conv->id,
            'sender_id'       => $tenant->id,
        ]);

        $response = $this->withToken($token)
            ->getJson("/api/conversations/{$conv->id}/messages/since?since_id={$lastMsg->id}");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_polling_marque_comme_lu(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $this->setUnreadCount($conv, $tenant, 5);

        $this->withToken($token)
            ->getJson("/api/conversations/{$conv->id}/messages/since?since_id=0")
            ->assertStatus(200);

        $pivot = $conv->fresh()->participants()->where('user_id', $tenant->id)->first()->pivot;
        $this->assertEquals(0, $pivot->unread_count);
    }

    public function test_polling_non_participant_retourne_403(): void
    {
        $tiers = $this->makeTenant();
        $token = $this->tokenFor($tiers);
        $conv  = $this->createConversation();

        $this->withToken($token)
            ->getJson("/api/conversations/{$conv->id}/messages/since?since_id=0")
            ->assertStatus(403);
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
