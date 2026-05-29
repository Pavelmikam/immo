<?php

namespace Tests\Feature\RentalRequest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;
use Tests\Traits\CreatesRentalRequests;

class CancelRentalRequestTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesRentalRequests;

    public function test_locataire_peut_annuler_sa_demande_en_attente(): void
    {
        $tenant  = $this->makeTenant();
        $token   = $this->tokenFor($tenant);
        $request = $this->createRentalRequest($tenant);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/cancel")
             ->assertStatus(200);

        $this->assertDatabaseHas('rental_requests', [
            'id'     => $request->id,
            'status' => 'annulee',
        ]);
    }

    public function test_locataire_ne_peut_pas_annuler_demande_acceptee(): void
    {
        $tenant  = $this->makeTenant();
        $token   = $this->tokenFor($tenant);
        $property = $this->createApprovedProperty();
        $request  = $this->createRentalRequest($tenant, $property, ['status' => 'acceptee']);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/cancel")
             ->assertStatus(422);
    }

    public function test_locataire_ne_peut_pas_annuler_demande_refusee(): void
    {
        $tenant  = $this->makeTenant();
        $token   = $this->tokenFor($tenant);
        $property = $this->createApprovedProperty();
        $request  = $this->createRentalRequest($tenant, $property, ['status' => 'refusee']);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/cancel")
             ->assertStatus(422);
    }

    public function test_locataire_ne_peut_pas_annuler_demande_dautrui(): void
    {
        $tenant  = $this->makeTenant();
        $other   = $this->makeTenant();
        $token   = $this->tokenFor($other);
        $request = $this->createRentalRequest($tenant);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/cancel")
             ->assertStatus(403);
    }

    public function test_proprietaire_ne_peut_pas_annuler(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->withToken($token)
             ->postJson("/api/rental-requests/{$request->id}/cancel")
             ->assertStatus(403);
    }

    public function test_non_authentifie_ne_peut_pas_annuler(): void
    {
        $request = $this->createRentalRequest();

        $this->postJson("/api/rental-requests/{$request->id}/cancel")
             ->assertStatus(401);
    }
}
