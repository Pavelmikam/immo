<?php

namespace Tests\Feature\Property;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesNeighborhoodData;
use Tests\Traits\CreatesProperties;

class PropertyNeighborhoodScoreTest extends TestCase
{
    use RefreshDatabase, CreatesProperties, CreatesNeighborhoodData;

    public function test_detail_bien_inclut_score_quartier_si_coordonnees(): void
    {
        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->active()->create([
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
            'district'  => 'Bastos',
        ]);

        $this->createReportsForZone('Yaoundé', 'Bastos', 5);

        $service = app(\App\Contracts\NeighborhoodScoreServiceInterface::class);
        $service->computeScore('Yaoundé', 'Bastos');

        $response = $this->getJson("/api/properties/{$property->id}");

        $response->assertStatus(200);
        $this->assertArrayHasKey('neighborhood_score', $response->json());
    }

    public function test_detail_bien_pas_de_score_si_sans_coordonnees(): void
    {
        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->active()->create([
            'latitude'  => null,
            'longitude' => null,
        ]);

        $response = $this->getJson("/api/properties/{$property->id}");

        $response->assertStatus(200);
        $this->assertNull($response->json('neighborhood_score'));
    }

    public function test_liste_bien_inclut_score_global_allege(): void
    {
        $owner    = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(3)->create([
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
        ]);

        $response = $this->getJson('/api/properties');

        $response->assertStatus(200);
        $this->assertArrayHasKey('neighborhood_global_score', $response->json('data.0'));
    }
}
