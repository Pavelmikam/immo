<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Notifications\MessageReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesConversations;
use Tests\Traits\CreatesProperties;

class MessageNotificationTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesConversations;

    private function makeTenant(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $attrs));
    }

    public function test_destinataire_notifie_quand_message_recu(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $conv     = $this->createConversation($tenant, $property);
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            ['body' => 'Bonjour !']
        )->assertStatus(201);

        Notification::assertSentTo($owner, MessageReceivedNotification::class);
    }

    public function test_sender_non_notifie_de_son_propre_message(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $conv     = $this->createConversation($tenant, $property);
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            ['body' => 'Bonjour !']
        )->assertStatus(201);

        Notification::assertNotSentTo($tenant, MessageReceivedNotification::class);
    }

    public function test_notification_message_contient_preview_50_chars(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $conv     = $this->createConversation($tenant, $property);
        $token    = $this->tokenFor($tenant);

        $longMessage = str_repeat('a', 100);

        $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            ['body' => $longMessage]
        )->assertStatus(201);

        Notification::assertSentTo($owner, MessageReceivedNotification::class,
            function (MessageReceivedNotification $notif) use ($owner) {
                $payload = $notif->toDatabase($owner);
                return mb_strlen($payload['body']) <= 53; // 50 chars + '...'
            }
        );
    }

    public function test_notification_non_envoyee_si_message_received_desactive(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $conv     = $this->createConversation($tenant, $property);

        $prefs = $owner->getOrCreateNotificationPreferences();
        $prefs->update(['enabled_types' => ['message_received' => false]]);

        $token = $this->tokenFor($tenant);
        $this->withToken($token)->postJson(
            "/api/conversations/{$conv->id}/messages",
            ['body' => 'Hello !']
        )->assertStatus(201);

        Notification::assertNotSentTo($owner, MessageReceivedNotification::class);
    }
}
