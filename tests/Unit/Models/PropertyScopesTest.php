<?php

namespace Tests\Unit\Models;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class PropertyScopesTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_scope_nearby_retourne_seulement_dans_rayon(): void
    {
        $owner = $this->makeProprietaire();
        // Yaoundé (~3.87, 11.52)
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(3.8667, 11.5167)->create();
        // Douala (~4.05, 9.70) — ~200km away
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(4.0500, 9.7000)->create();

        $results = Property::query()->public()->nearby(3.87, 11.52, 10)->get();

        $this->assertCount(1, $results);
        $this->assertEqualsWithDelta(3.8667, $results->first()->latitude, 0.001);
    }

    public function test_scope_nearby_exclut_annonces_sans_coordonnees(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()
                ->withCoords(3.8667, 11.5167)->create();
        Property::factory()->for($owner, 'owner')->active()->create([
            'latitude'  => null,
            'longitude' => null,
        ]);

        $results = Property::query()->public()->nearby(3.87, 11.52, 50)->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_hasAmenities_and_logique(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()
                ->withAmenities(['wifi', 'parking'])->create();
        Property::factory()->for($owner, 'owner')->active()
                ->withAmenities(['wifi'])->create();
        Property::factory()->for($owner, 'owner')->active()
                ->withAmenities(['parking'])->create();

        $results = Property::query()->public()->hasAmenities(['wifi', 'parking'])->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_sortBy_price_asc(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 300000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 100000]);
        Property::factory()->for($owner, 'owner')->active()->create(['price' => 200000]);

        $results = Property::query()->public()->sortBy('price_asc')->get();

        $this->assertEquals(100000, $results->first()->price);
        $this->assertEquals(300000, $results->last()->price);
    }

    public function test_scope_sortBy_newest(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['created_at' => now()->subHours(2)]);
        Property::factory()->for($owner, 'owner')->active()->create(['created_at' => now()->subHour()]);
        $newest = Property::factory()->for($owner, 'owner')->active()->create(['created_at' => now()]);

        $results = Property::query()->public()->sortBy('newest')->get();

        $this->assertEquals($newest->id, $results->first()->id);
    }

    public function test_scope_availableFrom_inclut_null_et_passe(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->create(['available_from' => now()->subDay()]);
        Property::factory()->for($owner, 'owner')->active()->create(['available_from' => now()->addMonths(2)]);
        Property::factory()->for($owner, 'owner')->active()->create(['available_from' => null]);

        $results = Property::query()
            ->public()
            ->where(function ($q) {
                $q->whereNull('available_from')
                  ->orWhere('available_from', '<=', now()->toDateString());
            })
            ->get();

        $this->assertCount(2, $results); // past + null
    }
}
