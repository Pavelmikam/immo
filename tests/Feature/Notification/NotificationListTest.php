<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationListTest extends TestCase
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
            'id'              => \Illuminate\Support\Str::uuid(),
            'type'            => 'App\Notifications\RentalRequestReceivedNotification',
            'data'            => array_merge([
                'type'  => 'rental_request_received',
                'title' => 'Nouvelle demande',
                'body'  => 'Corps de la notification',
            ], $data['data'] ?? []),
            'read_at'         => $data['read_at'] ?? null,
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
        ], array_diff_key($data, ['data' => null])));
    }

    public function test_utilisateur_peut_lister_ses_notifications(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->createNotif($user);
        $this->createNotif($user);
        $this->createNotif($user);

        $response = $this->withToken($token)->getJson('/api/notifications');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_filtre_unread_retourne_seulement_non_lues(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->createNotif($user);
        $this->createNotif($user);
        $this->createNotif($user, ['read_at' => now()]);

        $response = $this->withToken($token)->getJson('/api/notifications?unread=true');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filtre_par_type(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->createNotif($user, ['data' => ['type' => 'rental_request_received', 'title' => 'T', 'body' => 'B']]);
        $this->createNotif($user, ['data' => ['type' => 'rental_request_received', 'title' => 'T', 'body' => 'B']]);
        $this->createNotif($user, ['data' => ['type' => 'message_received', 'title' => 'T', 'body' => 'B']]);

        $response = $this->withToken($token)->getJson('/api/notifications?type=rental_request_received');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_notifications_paginees_par_20(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        for ($i = 0; $i < 25; $i++) {
            $this->createNotif($user);
        }

        $response = $this->withToken($token)->getJson('/api/notifications');

        $response->assertStatus(200);
        $this->assertCount(20, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    public function test_non_authentifie_ne_peut_pas_voir_notifications(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
    }

    public function test_isolation_ne_voit_pas_notifications_dautrui(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();

        $this->createNotif($userA);
        $this->createNotif($userA);
        $this->createNotif($userA);
        $this->createNotif($userB);
        $this->createNotif($userB);

        $token    = $userA->createToken('test')->plainTextToken;
        $response = $this->withToken($token)->getJson('/api/notifications');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_structure_notification_resource_correcte(): void
    {
        $user  = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->createNotif($user);

        $response = $this->withToken($token)->getJson('/api/notifications');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [['id', 'type', 'title', 'body', 'is_read', 'created_at']],
                 ]);

        $response->assertJsonMissingExact(['notifiable_id' => null]);
        $this->assertArrayNotHasKey('notifiable_type', $response->json('data.0'));
    }
}
