<?php

namespace Tests\Feature\Notification;

use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\User;
use App\Notifications\RentalRequestAcceptedNotification;
use App\Notifications\RentalRequestReceivedNotification;
use App\Notifications\RentalRequestRefusedNotification;
use App\Notifications\VisitScheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;
use Tests\Traits\CreatesRentalRequests;

class RentalRequestNotificationTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesRentalRequests;

    private function makeVerifiedTenant(): User
    {
        return User::factory()->create([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
    }

    public function test_proprietaire_notifie_quand_demande_recue(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeVerifiedTenant();
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)->postJson(
            "/api/rental-requests/properties/{$property->id}",
            ['message' => 'Je suis intéressé.']
        )->assertStatus(201);

        Notification::assertSentTo($owner, RentalRequestReceivedNotification::class);
        Notification::assertNotSentTo($tenant, RentalRequestReceivedNotification::class);
    }

    public function test_locataire_notifie_quand_demande_acceptee(): void
    {
        Notification::fake();

        $owner          = $this->makeProprietaire();
        $property       = $this->createApprovedProperty($owner);
        $tenant         = $this->makeVerifiedTenant();
        $rentalRequest  = $this->createRentalRequest($tenant, $property);
        $token          = $this->tokenFor($owner);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$rentalRequest->id}/decide",
            ['action' => 'accept']
        )->assertStatus(200);

        Notification::assertSentTo($tenant, RentalRequestAcceptedNotification::class);
    }

    public function test_locataire_notifie_quand_demande_refusee(): void
    {
        Notification::fake();

        $owner         = $this->makeProprietaire();
        $property      = $this->createApprovedProperty($owner);
        $tenant        = $this->makeVerifiedTenant();
        $rentalRequest = $this->createRentalRequest($tenant, $property);
        $token         = $this->tokenFor($owner);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$rentalRequest->id}/decide",
            ['action' => 'refuse', 'owner_response' => 'Dossier incomplet.']
        )->assertStatus(200);

        Notification::assertSentTo($tenant, RentalRequestRefusedNotification::class);
    }

    public function test_locataire_notifie_quand_visite_planifiee(): void
    {
        Notification::fake();

        $owner         = $this->makeProprietaire();
        $property      = $this->createApprovedProperty($owner);
        $tenant        = $this->makeVerifiedTenant();
        $rentalRequest = $this->createRentalRequest($tenant, $property);
        $token         = $this->tokenFor($owner);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$rentalRequest->id}/schedule-visit",
            ['visit_scheduled_at' => now()->addDays(3)->toIso8601String()]
        )->assertStatus(200);

        Notification::assertSentTo($tenant, VisitScheduledNotification::class);
    }

    public function test_notification_enregistree_en_base(): void
    {
        $owner         = $this->makeProprietaire();
        $property      = $this->createApprovedProperty($owner);
        $tenant        = $this->makeVerifiedTenant();
        $rentalRequest = $this->createRentalRequest($tenant, $property);
        $token         = $this->tokenFor($owner);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$rentalRequest->id}/decide",
            ['action' => 'accept']
        )->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $tenant->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_notification_non_envoyee_si_type_desactive(): void
    {
        Notification::fake();

        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeVerifiedTenant();

        $prefs = $owner->getOrCreateNotificationPreferences();
        $prefs->update(['enabled_types' => ['rental_request_received' => false]]);

        $token = $this->tokenFor($tenant);
        $this->withToken($token)->postJson(
            "/api/rental-requests/properties/{$property->id}",
            ['message' => 'Je suis intéressé.']
        )->assertStatus(201);

        Notification::assertNotSentTo($owner, RentalRequestReceivedNotification::class);
    }
}
