<?php

namespace Tests\Feature\Search;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class PropertyMapTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_endpoint_map_retourne_seulement_biens_avec_coordonnees(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(3)
                ->withCoords(3.8667, 11.5167)->create();
        Property::factory()->for($owner, 'owner')->active()->count(2)->create([
            'latitude'  => null,
            'longitude' => null,
        ]);

        $response = $this->getJson('/api/properties/map');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_endpoint_map_retourne_structure_allegee(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(3.8667, 11.5167)->create();

        $response = $this->getJson('/api/properties/map');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'latitude', 'longitude', 'price', 'type']]]);

        $this->assertArrayNotHasKey('description', $response->json('data.0'));
        $this->assertArrayNotHasKey('address', $response->json('data.0'));
    }

    public function test_endpoint_map_accepte_filtres_de_recherche(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(3.8667, 11.5167)->create(['city' => 'Yaoundé', 'type' => 'studio']);
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(4.0500, 9.7000)->create(['city' => 'Douala', 'type' => 'house']);

        $response = $this->getJson('/api/properties/map?city=Yaoundé&type=studio');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_endpoint_map_limite_a_200_resultats(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(3.8667, 11.5167)->count(250)->create();

        $response = $this->getJson('/api/properties/map');

        $response->assertStatus(200);
        $this->assertCount(200, $response->json('data'));
    }

    public function test_endpoint_map_route_avant_show_property(): void
    {
        $response = $this->getJson('/api/properties/map');

        // Should return 200 (no properties) not 404 (route conflict)
        $response->assertStatus(200);
        $this->assertArrayHasKey('data', $response->json());
    }
}
