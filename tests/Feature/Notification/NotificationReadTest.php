<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationReadTest extends TestCase
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

    private function createNotif(User $user, array $data = []): object
    {
        return $user->notifications()->create(array_merge([
            'id'              => Str::uuid(),
            'type'            => 'App\Notifications\RentalRequestReceivedNotification',
            'data'            => [
                'type'  => 'rental_request_received',
                'title' => 'Test',
                'body'  => 'Corps',
            ],
            'read_at'         => null,
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
        ], $data));
    }

    public function test_peut_marquer_une_notification_comme_lue(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;
        $notif = $this->createNotif($user);

        $response = $this->withToken($token)
                         ->postJson("/api/notifications/{$notif->id}/read");

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_read', true);

        $this->assertNotNull(
            $user->notifications()->find($notif->id)->read_at
        );
    }

    public function test_peut_marquer_toutes_comme_lues(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        for ($i = 0; $i < 5; $i++) {
            $this->createNotif($user);
        }

        $this->withToken($token)
             ->postJson('/api/notifications/mark-all-read')
             ->assertStatus(200);

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_peut_supprimer_une_notification(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;
        $notif = $this->createNotif($user);

        $this->withToken($token)
             ->deleteJson("/api/notifications/{$notif->id}")
             ->assertStatus(204);

        $this->assertDatabaseMissing('notifications', ['id' => $notif->id]);
    }

    public function test_badge_unread_count_retourne_bon_chiffre(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->createNotif($user);
        $this->createNotif($user);
        $this->createNotif($user);
        $this->createNotif($user, ['read_at' => now()]);
        $this->createNotif($user, ['read_at' => now()]);

        $response = $this->withToken($token)->getJson('/api/notifications/unread-count');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('count'));
    }

    public function test_badge_diminue_apres_lecture(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $notif1 = $this->createNotif($user);
        $this->createNotif($user);
        $this->createNotif($user);

        $this->withToken($token)->postJson("/api/notifications/{$notif1->id}/read");

        $response = $this->withToken($token)->getJson('/api/notifications/unread-count');
        $this->assertEquals(2, $response->json('count'));
    }

    public function test_ne_peut_pas_modifier_notif_dautrui(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $notif = $this->createNotif($userA);

        $tokenB = $userB->createToken('test')->plainTextToken;

        $this->withToken($tokenB)
             ->postJson("/api/notifications/{$notif->id}/read")
             ->assertStatus(404);
    }

    public function test_ne_peut_pas_supprimer_notif_dautrui(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $notif = $this->createNotif($userA);

        $tokenB = $userB->createToken('test')->plainTextToken;

        $this->withToken($tokenB)
             ->deleteJson("/api/notifications/{$notif->id}")
             ->assertStatus(404);
    }
}
