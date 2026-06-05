<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $attrs));
    }

    public function test_peut_consulter_ses_preferences(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/notification-preferences');

        $response->assertStatus(200)
                 ->assertJsonStructure(['channels', 'enabled_types', 'available_types']);
    }

    public function test_preferences_creees_automatiquement_si_absentes(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->assertDatabaseMissing('notification_preferences', ['user_id' => $user->id]);

        $this->withToken($token)->getJson('/api/notification-preferences')->assertStatus(200);

        $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id]);
    }

    public function test_peut_desactiver_canal_email(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/notification-preferences', [
            'channels' => ['mail' => false],
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('channels.mail'));

        $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id]);
    }

    public function test_peut_desactiver_type_specifique(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/notification-preferences', [
            'enabled_types' => ['message_received' => false],
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('enabled_types.message_received'));
    }

    public function test_non_authentifie_ne_peut_pas_modifier(): void
    {
        $this->putJson('/api/notification-preferences', [
            'channels' => ['mail' => false],
        ])->assertStatus(401);
    }

    public function test_type_inconnu_dans_preferences_ignore(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/notification-preferences', [
            'enabled_types' => ['type_inexistant' => false],
        ]);

        $response->assertStatus(200);
    }
}
