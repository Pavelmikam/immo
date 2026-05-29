<?php

namespace Tests\Feature\RentalRequest;

use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;
use Tests\Traits\CreatesRentalRequests;

class CreateRentalRequestTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesRentalRequests;

    public function test_locataire_peut_postuler_sur_bien_actif(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $response = $this->withToken($token)->postJson(
            "/api/rental-requests/properties/{$property->id}",
            ['message' => 'Je suis intéressé par votre bien et souhaite postuler.']
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('rental_requests', [
            'property_id' => $property->id,
            'tenant_id'   => $tenant->id,
            'status'      => 'en_attente',
        ]);
        $this->assertEquals(1, $property->fresh()->requests_count);
    }

    public function test_non_authentifie_ne_peut_pas_postuler(): void
    {
        $property = $this->createApprovedProperty();

        $this->postJson("/api/rental-requests/properties/{$property->id}")
             ->assertStatus(401);
    }

    public function test_proprietaire_ne_peut_pas_postuler(): void
    {
        $owner = $this->makeProprietaire();
        $property = $this->createApprovedProperty();
        $token = $this->tokenFor($owner);

        $this->withToken($token)
             ->postJson("/api/rental-requests/properties/{$property->id}")
             ->assertStatus(403);
    }

    public function test_email_non_verifie_ne_peut_pas_postuler(): void
    {
        $tenant = $this->makeTenant(['email_verified_at' => null]);
        $property = $this->createApprovedProperty();
        $token = $this->tokenFor($tenant);

        $this->withToken($token)
             ->postJson("/api/rental-requests/properties/{$property->id}")
             ->assertStatus(403);
    }

    public function test_impossible_postuler_sur_bien_non_actif(): void
    {
        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->pending()->create();
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)
             ->postJson("/api/rental-requests/properties/{$property->id}")
             ->assertStatus(422)
             ->assertJsonPath('message', 'Ce bien n\'est pas disponible à la location.');
    }

    public function test_impossible_double_demande_active_meme_bien(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $this->createRentalRequest($tenant, $property);

        $this->withToken($token)
             ->postJson("/api/rental-requests/properties/{$property->id}")
             ->assertStatus(422)
             ->assertJsonPath('message', 'Vous avez déjà une demande en cours pour ce bien.');
    }

    public function test_possible_repostuler_apres_refus(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $this->createRentalRequest($tenant, $property, ['status' => 'refusee']);

        $this->withToken($token)
             ->postJson("/api/rental-requests/properties/{$property->id}")
             ->assertStatus(201);
    }

    public function test_possible_repostuler_apres_annulation(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $this->createRentalRequest($tenant, $property, ['status' => 'annulee']);

        $this->withToken($token)
             ->postJson("/api/rental-requests/properties/{$property->id}")
             ->assertStatus(201);
    }

    public function test_proprietaire_ne_peut_pas_postuler_sur_son_propre_bien(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);

        // Le proprio devient aussi locataire (role change)
        $owner->update(['role' => 'locataire', 'email_verified_at' => now()]);
        $token = $this->tokenFor($owner);

        $this->withToken($token)
             ->postJson("/api/rental-requests/properties/{$property->id}")
             ->assertStatus(422)
             ->assertJsonPath('message', 'Vous ne pouvez pas postuler sur votre propre bien.');
    }

    public function test_message_trop_court_refuse(): void
    {
        $property = $this->createApprovedProperty();
        $tenant   = $this->makeTenant();
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)
             ->postJson("/api/rental-requests/properties/{$property->id}", ['message' => 'hi'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['message']);
    }
}
