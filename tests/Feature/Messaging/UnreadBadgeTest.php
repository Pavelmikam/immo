<?php

namespace Tests\Feature\Messaging;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesConversations;
use Tests\Traits\CreatesProperties;

class UnreadBadgeTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesConversations;

    public function test_badge_retourne_zero_si_aucun_message_non_lu(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);

        $this->createConversation($tenant);

        $response = $this->withToken($token)->getJson('/api/messaging/unread-count');

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('unread_count'));
    }

    public function test_badge_retourne_total_non_lus_sur_toutes_conversations(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);

        $conv1 = $this->createConversation($tenant);
        $conv2 = $this->createConversation($tenant);

        $this->setUnreadCount($conv1, $tenant, 3);
        $this->setUnreadCount($conv2, $tenant, 2);

        $response = $this->withToken($token)->getJson('/api/messaging/unread-count');

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('unread_count'));
    }

    public function test_badge_diminue_apres_lecture(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);
        $conv   = $this->createConversation($tenant);

        $this->setUnreadCount($conv, $tenant, 3);

        // Ouvrir la conversation marque comme lu
        $this->withToken($token)->getJson("/api/conversations/{$conv->id}");

        $response = $this->withToken($token)->getJson('/api/messaging/unread-count');
        $this->assertEquals(0, $response->json('unread_count'));
    }

    public function test_badge_non_authentifie_retourne_401(): void
    {
        $this->getJson('/api/messaging/unread-count')->assertStatus(401);
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
