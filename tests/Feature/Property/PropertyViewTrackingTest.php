<?php

namespace Tests\Feature\Property;

use App\Models\Property;
use App\Models\PropertyView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class PropertyViewTrackingTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_vue_enregistree_dans_property_views(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);

        $this->getJson("/api/properties/{$property->id}");

        $this->assertDatabaseHas('property_views', ['property_id' => $property->id]);
    }

    public function test_views_count_incremente(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $initial  = $property->views_count;

        $this->getJson("/api/properties/{$property->id}");

        $this->assertEquals($initial + 1, $property->fresh()->views_count);
    }

    public function test_vue_non_comptee_pour_proprietaire(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)->getJson("/api/properties/{$property->id}");

        $this->assertDatabaseMissing('property_views', [
            'property_id' => $property->id,
            'user_id'     => $owner->id,
        ]);
    }

    public function test_anti_doublon_30_minutes(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $session  = 'test-session-' . uniqid();

        // Simuler même session deux fois
        $property->recordView(null, $session, '127.0.0.1');
        $property->recordView(null, $session, '127.0.0.1');

        $this->assertDatabaseCount('property_views', 1);
    }

    public function test_session_differente_compte_comme_nouvelle_vue(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);

        $property->recordView(null, 'session-1', '127.0.0.1');
        $property->recordView(null, 'session-2', '127.0.0.2');

        $this->assertDatabaseCount('property_views', 2);
    }
}
