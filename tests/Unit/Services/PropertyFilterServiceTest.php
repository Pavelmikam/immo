<?php

namespace Tests\Unit\Services;

use App\Models\Property;
use App\Services\PropertyFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class PropertyFilterServiceTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    private PropertyFilterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PropertyFilterService();
    }

    public function test_buildQuery_sans_filtres_retourne_seulement_public(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(3)->create();
        Property::factory()->for($owner, 'owner')->pending()->count(2)->create();

        $this->assertEquals(3, $this->service->buildQuery([])->count());
    }

    public function test_buildQuery_filtre_par_city(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['city' => 'Yaoundé']);
        Property::factory()->for($owner, 'owner')->active()->create(['city' => 'Douala']);

        $this->assertEquals(1, $this->service->buildQuery(['city' => 'Yaoundé'])->count());
    }

    public function test_buildQuery_filtre_par_price_min(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 30000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 100000]);

        $this->assertEquals(1, $this->service->buildQuery(['price_min' => 50000])->count());
    }

    public function test_buildQuery_filtre_par_price_max(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 30000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 100000]);

        $this->assertEquals(1, $this->service->buildQuery(['price_max' => 50000])->count());
    }

    public function test_buildQuery_filtre_par_surface_min(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['surface' => 30]);
        Property::factory()->for($owner, 'owner')->active()->create(['surface' => 100]);

        $this->assertEquals(1, $this->service->buildQuery(['surface_min' => 50])->count());
    }

    public function test_buildQuery_filtre_par_rooms_min(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['rooms' => 1]);
        Property::factory()->for($owner, 'owner')->active()->create(['rooms' => 4]);

        $this->assertEquals(1, $this->service->buildQuery(['rooms_min' => 3])->count());
    }

    public function test_buildQuery_filtre_par_amenities_and(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()
                ->withAmenities(['wifi', 'parking'])->create();
        Property::factory()->for($owner, 'owner')->active()
                ->withAmenities(['wifi'])->create();
        Property::factory()->for($owner, 'owner')->active()
                ->withAmenities(['parking'])->create();

        $count = $this->service->buildQuery(['amenities' => ['wifi', 'parking']])->count();
        $this->assertEquals(1, $count);
    }

    public function test_buildQuery_filtre_par_geoloc_radius(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(3.8667, 11.5167)->create();
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(4.0500, 9.7000)->create();

        // Near Yaoundé — should return 1
        $nearYaounde = $this->service->buildQuery([
            'latitude'  => 3.87,
            'longitude' => 11.52,
            'radius_km' => 5,
        ])->count();
        $this->assertEquals(1, $nearYaounde);

        // Near Douala — should return 1, not the Yaoundé property
        $nearDouala = $this->service->buildQuery([
            'latitude'  => 4.05,
            'longitude' => 9.70,
            'radius_km' => 5,
        ])->count();
        $this->assertEquals(1, $nearDouala);
    }

    public function test_buildQuery_tri_price_asc(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 200]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 100]);

        $results = $this->service->buildQuery(['sort' => 'price_asc'])->get();
        $this->assertEquals(100, $results->first()->price);
    }

    public function test_buildQuery_radius_max_50km_respecte(): void
    {
        $owner = $this->makeProprietaire();
        // Property far away (>50km from reference)
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(10.0, 15.0)->create();

        // With radius_km=200 (should be capped to 50km)
        $filters  = ['latitude' => 3.87, 'longitude' => 11.52, 'radius_km' => 200];
        $query    = $this->service->buildQuery($filters);
        $sqlBindings = $query->getBindings();

        // Last binding should be 50 (capped)
        $this->assertEquals(50.0, end($sqlBindings));
    }
}
