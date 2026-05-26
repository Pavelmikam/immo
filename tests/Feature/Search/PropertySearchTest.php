<?php

namespace Tests\Feature\Search;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class PropertySearchTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_guest_peut_rechercher_sans_filtre(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(3)->create();

        $this->getJson('/api/properties')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_filtre_par_city_retourne_bonne_ville(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(3)->create(['city' => 'Yaoundé']);
        Property::factory()->for($owner, 'owner')->active()->count(2)->create(['city' => 'Douala']);

        $response = $this->getJson('/api/properties?city=Yaoundé');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_filtre_par_type_retourne_bon_type(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(2)->create(['type' => 'studio']);
        Property::factory()->for($owner, 'owner')->active()->count(3)->create(['type' => 'house']);

        $response = $this->getJson('/api/properties?type=studio');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filtre_par_price_min_exclut_moins_cher(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 30000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 50000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 100000]);

        $response = $this->getJson('/api/properties?price_min=60000');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(100000, $response->json('data.0.price'));
    }

    public function test_filtre_par_price_max_exclut_plus_cher(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 30000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 50000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 100000]);

        $response = $this->getJson('/api/properties?price_max=60000');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filtre_prix_min_max_combine(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 30000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 50000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 100000]);

        $response = $this->getJson('/api/properties?price_min=40000&price_max=80000');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(50000, $response->json('data.0.price'));
    }

    public function test_price_max_inferieur_price_min_retourne_422(): void
    {
        $this->getJson('/api/properties?price_min=100000&price_max=50000')
             ->assertStatus(422)
             ->assertJsonValidationErrors(['price_max']);
    }

    public function test_filtre_par_rooms_min(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['rooms' => 1]);
        Property::factory()->for($owner, 'owner')->active()->create(['rooms' => 2]);
        Property::factory()->for($owner, 'owner')->active()->create(['rooms' => 4]);

        $response = $this->getJson('/api/properties?rooms_min=3');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filtre_par_amenities_and_logique(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()
                ->withAmenities(['wifi', 'parking'])->create();
        Property::factory()->for($owner, 'owner')->active()
                ->withAmenities(['wifi'])->create();
        Property::factory()->for($owner, 'owner')->active()
                ->withAmenities(['parking'])->create();

        $response = $this->getJson('/api/properties?amenities[]=wifi&amenities[]=parking');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filtre_par_available_from(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['available_from' => now()->subDay()]);
        Property::factory()->for($owner, 'owner')->active()->create(['available_from' => now()->addMonths(2)]);
        Property::factory()->for($owner, 'owner')->active()->create(['available_from' => null]);

        $response = $this->getJson('/api/properties?available_from=' . now()->toDateString());

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data')); // past + null
    }

    public function test_tri_par_prix_asc(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 150000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 50000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 100000]);

        $response = $this->getJson('/api/properties?sort=price_asc');

        $data = $response->json('data');
        $this->assertLessThanOrEqual($data[1]['price'], $data[0]['price']); // data[0] <= data[1]
        $this->assertLessThanOrEqual($data[2]['price'], $data[1]['price']); // data[1] <= data[2]
        $this->assertEquals(50000, $data[0]['price']);
    }

    public function test_tri_par_prix_desc(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 150000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 50000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 100000]);

        $response = $this->getJson('/api/properties?sort=price_desc');

        $data = $response->json('data');
        $this->assertEquals(150000, $data[0]['price']);
        $this->assertGreaterThanOrEqual($data[1]['price'], $data[0]['price']);
    }

    public function test_tri_par_newest_par_defaut(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['created_at' => now()->subHours(2)]);
        Property::factory()->for($owner, 'owner')->active()->create(['created_at' => now()->subHour()]);
        $newest = Property::factory()->for($owner, 'owner')->active()->create(['created_at' => now()]);

        $response = $this->getJson('/api/properties');

        $this->assertEquals($newest->id, $response->json('data.0.id'));
    }

    public function test_per_page_personnalise(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(20)->create();

        $response = $this->getJson('/api/properties?per_page=5');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(5, $response->json('meta.per_page'));
    }

    public function test_per_page_max_50_respecte(): void
    {
        $this->getJson('/api/properties?per_page=100')->assertStatus(422);
    }

    public function test_annonces_non_approuvees_exclues_de_recherche(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(5)->create();
        Property::factory()->for($owner, 'owner')->pending()->count(5)->create();

        $response = $this->getJson('/api/properties');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_filtre_par_geolocalisation_radius(): void
    {
        $owner = $this->makeProprietaire();
        // Yaoundé (~3.87, 11.52)
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(3.8667, 11.5167)->create();
        // Douala (~4.05, 9.70) — ~200km away
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(4.0500, 9.7000)->create();

        $response = $this->getJson('/api/properties?latitude=3.8667&longitude=11.5167&radius_km=10');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_latitude_sans_longitude_retourne_422(): void
    {
        $this->getJson('/api/properties?latitude=3.8667')
             ->assertStatus(422);
    }

    public function test_is_favorited_false_pour_visiteur(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create();

        $response = $this->getJson('/api/properties');

        $this->assertFalse($response->json('data.0.is_favorited'));
    }

    public function test_is_favorited_true_pour_bien_en_favori(): void
    {
        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->active()->create();
        $locataire = User::factory()->locataire()->create();
        $locataire->favorites()->attach($property->id);

        $token = $this->tokenFor($locataire);

        $response = $this->withToken($token)->getJson('/api/properties');

        $found = collect($response->json('data'))->firstWhere('id', $property->id);
        $this->assertTrue($found['is_favorited']);
    }
}
