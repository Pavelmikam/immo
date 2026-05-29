<?php

namespace Tests\Feature\RentalRequest;

use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;
use Tests\Traits\CreatesRentalRequests;

class DecideRentalRequestTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesRentalRequests;

    public function test_proprietaire_peut_accepter_demande(): void
    {
        $owner   = $this->makeProprietaire();
        $token   = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $response = $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/decide",
            ['action' => 'accept', 'owner_response' => 'Bienvenue !']
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('rental_requests', [
            'id'     => $request->id,
            'status' => 'acceptee',
        ]);
        $this->assertDatabaseHas('properties', [
            'id'     => $property->id,
            'status' => 'sous_reservation',
        ]);
    }

    public function test_accepter_refuse_automatiquement_autres_demandes(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);

        $req1 = $this->createRentalRequest(null, $property);
        $req2 = $this->createRentalRequest(null, $property);
        $req3 = $this->createRentalRequest(null, $property);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$req1->id}/decide",
            ['action' => 'accept']
        )->assertStatus(200);

        $this->assertDatabaseHas('rental_requests', ['id' => $req1->id, 'status' => 'acceptee']);
        $this->assertDatabaseHas('rental_requests', ['id' => $req2->id, 'status' => 'refusee']);
        $this->assertDatabaseHas('rental_requests', ['id' => $req3->id, 'status' => 'refusee']);
    }

    public function test_proprietaire_peut_refuser_demande(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $response = $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/decide",
            ['action' => 'refuse', 'owner_response' => 'Profil non retenu.']
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('rental_requests', [
            'id'             => $request->id,
            'status'         => 'refusee',
            'owner_response' => 'Profil non retenu.',
        ]);
    }

    public function test_refus_sans_reponse_proprietaire_invalide(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/decide",
            ['action' => 'refuse']
        )->assertStatus(422)->assertJsonValidationErrors(['owner_response']);
    }

    public function test_non_proprietaire_ne_peut_pas_decider(): void
    {
        $owner    = $this->makeProprietaire();
        $other    = $this->makeProprietaire();
        $token    = $this->tokenFor($other);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/decide",
            ['action' => 'accept']
        )->assertStatus(403);
    }

    public function test_locataire_ne_peut_pas_decider(): void
    {
        $owner    = $this->makeProprietaire();
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest($tenant, $property);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/decide",
            ['action' => 'accept']
        )->assertStatus(403);
    }

    public function test_impossible_decider_demande_deja_traitee(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property, ['status' => 'acceptee']);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/decide",
            ['action' => 'refuse', 'owner_response' => 'Trop tard.']
        )->assertStatus(422);
    }

    public function test_action_invalide_retourne_422(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);
        $request  = $this->createRentalRequest(null, $property);

        $this->withToken($token)->postJson(
            "/api/rental-requests/{$request->id}/decide",
            ['action' => 'ignorer']
        )->assertStatus(422)->assertJsonValidationErrors(['action']);
    }
}
