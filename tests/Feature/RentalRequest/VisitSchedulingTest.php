<?php

namespace Tests\Feature\RentalRequest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;
use Tests\Traits\CreatesRentalRequests;

class VisitSchedulingTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesRentalRequests;

    public function test_proprietaire_peut_planifier_visite(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $visitDate = now()->addDays(3)->toISOString();

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/schedule-visit", [
                 'visit_scheduled_at' => $visitDate,
             ])
             ->assertStatus(200);

        $this->assertDatabaseHas('rental_requests', [
            'id'              => $request->id,
            'visit_confirmed' => false,
        ]);
        $this->assertNotNull($request->fresh()->visit_scheduled_at);
    }

    public function test_locataire_ne_peut_pas_planifier_visite(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);
        $request  = $this->createRentalRequest($tenant, $property);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/schedule-visit", [
                 'visit_scheduled_at' => now()->addDays(3)->toISOString(),
             ])
             ->assertStatus(403);
    }

    public function test_date_dans_le_passe_refusee(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/schedule-visit", [
                 'visit_scheduled_at' => now()->subDay()->toISOString(),
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['visit_scheduled_at']);
    }

    public function test_date_manquante_refusee(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/schedule-visit", [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['visit_scheduled_at']);
    }

    public function test_locataire_peut_confirmer_visite(): void
    {
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest($tenant, $property, [
            'visit_scheduled_at' => now()->addDays(2),
        ]);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/confirm-visit")
             ->assertStatus(200);

        $this->assertDatabaseHas('rental_requests', [
            'id'              => $request->id,
            'visit_confirmed' => true,
        ]);
    }

    public function test_proprietaire_ne_peut_pas_confirmer_visite(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property, [
            'visit_scheduled_at' => now()->addDays(2),
        ]);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/confirm-visit")
             ->assertStatus(403);
    }

    public function test_confirmer_visite_sans_planification_retourne_422(): void
    {
        $tenant  = $this->makeTenant();
        $token   = $this->tokenFor($tenant);
        $request = $this->createRentalRequest($tenant);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/confirm-visit")
             ->assertStatus(422);
    }
}
