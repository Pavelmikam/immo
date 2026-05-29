<?php

namespace Tests\Feature\RentalRequest;

use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;
use Tests\Traits\CreatesRentalRequests;

class ListRentalRequestsTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesRentalRequests;

    public function test_locataire_voit_ses_propres_demandes(): void
    {
        $tenant  = $this->makeTenant();
        $token   = $this->tokenFor($tenant);
        $other   = $this->makeTenant();

        $this->createRentalRequest($tenant);
        $this->createRentalRequest($tenant);
        $this->createRentalRequest($other);

        $response = $this->withToken($token)->getJson('/api/rental-requests');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_locataire_ne_voit_pas_demandes_des_autres(): void
    {
        $tenant = $this->makeTenant();
        $other  = $this->makeTenant();
        $token  = $this->tokenFor($tenant);

        $this->createRentalRequest($other);

        $response = $this->withToken($token)->getJson('/api/rental-requests');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_proprietaire_voit_demandes_sur_ses_biens(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $property = $this->createApprovedProperty($owner);

        $this->createRentalRequest(null, $property);
        $this->createRentalRequest(null, $property);

        $response = $this->withToken($token)->getJson('/api/rental-requests');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_proprietaire_ne_voit_pas_demandes_sur_biens_dautrui(): void
    {
        $owner    = $this->makeProprietaire();
        $token    = $this->tokenFor($owner);
        $other    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($other);

        $this->createRentalRequest(null, $property);

        $response = $this->withToken($token)->getJson('/api/rental-requests');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_admin_voit_toutes_les_demandes(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'is_active' => true]);
        $token = $this->tokenFor($admin);

        $this->createRentalRequest();
        $this->createRentalRequest();
        $this->createRentalRequest();

        $response = $this->withToken($token)->getJson('/api/rental-requests');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_liste_paginee_par_15(): void
    {
        $tenant = $this->makeTenant();
        $token  = $this->tokenFor($tenant);

        for ($i = 0; $i < 20; $i++) {
            $this->createRentalRequest($tenant);
        }

        $response = $this->withToken($token)->getJson('/api/rental-requests');

        $response->assertStatus(200);
        $this->assertCount(15, $response->json('data'));
        $this->assertNotNull($response->json('links.next'));
    }

    public function test_non_authentifie_ne_peut_pas_voir_liste(): void
    {
        $this->getJson('/api/rental-requests')->assertStatus(401);
    }
}
