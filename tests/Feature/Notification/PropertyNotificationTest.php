<?php

namespace Tests\Feature\Notification;

use App\Events\PropertyApproved;
use App\Events\PropertyRejected;
use App\Models\User;
use App\Notifications\PropertyApprovedNotification;
use App\Notifications\PropertyRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class PropertyNotificationTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role'              => 'admin',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
    }

    public function test_proprietaire_notifie_quand_annonce_approuvee(): void
    {
        Notification::fake();

        $admin    = $this->makeAdmin();
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $token    = $this->tokenFor($admin);

        $this->withToken($token)->postJson(
            "/api/admin/properties/{$property->id}/moderate",
            ['action' => 'approve']
        )->assertStatus(200);

        Notification::assertSentTo($owner, PropertyApprovedNotification::class);
    }

    public function test_proprietaire_notifie_quand_annonce_rejetee(): void
    {
        Notification::fake();

        $admin    = $this->makeAdmin();
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $token    = $this->tokenFor($admin);

        $this->withToken($token)->postJson(
            "/api/admin/properties/{$property->id}/moderate",
            ['action' => 'reject', 'reason' => 'Photos insuffisantes.']
        )->assertStatus(200);

        Notification::assertSentTo($owner, PropertyRejectedNotification::class);
    }

    public function test_notification_rejet_contient_motif(): void
    {
        Notification::fake();

        $admin    = $this->makeAdmin();
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $token    = $this->tokenFor($admin);

        $this->withToken($token)->postJson(
            "/api/admin/properties/{$property->id}/moderate",
            ['action' => 'reject', 'reason' => 'Photos insuffisantes.']
        );

        Notification::assertSentTo($owner, PropertyRejectedNotification::class,
            function (PropertyRejectedNotification $notif) use ($owner) {
                $payload = $notif->toDatabase($owner);
                return $payload['rejection_reason'] === 'Photos insuffisantes.';
            }
        );
    }

    public function test_event_property_approved_dispatche(): void
    {
        Event::fake([PropertyApproved::class]);

        $admin    = $this->makeAdmin();
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $token    = $this->tokenFor($admin);

        $this->withToken($token)->postJson(
            "/api/admin/properties/{$property->id}/moderate",
            ['action' => 'approve']
        );

        Event::assertDispatched(PropertyApproved::class,
            fn ($e) => $e->property->id === $property->id
        );
    }

    public function test_event_property_rejected_dispatche(): void
    {
        Event::fake([PropertyRejected::class]);

        $admin    = $this->makeAdmin();
        $owner    = $this->makeProprietaire();
        $property = $this->makeProperty($owner, ['status' => 'pending']);
        $token    = $this->tokenFor($admin);

        $this->withToken($token)->postJson(
            "/api/admin/properties/{$property->id}/moderate",
            ['action' => 'reject', 'reason' => 'Annonce trop vague.']
        );

        Event::assertDispatched(PropertyRejected::class,
            fn ($e) => $e->property->id === $property->id
        );
    }
}
