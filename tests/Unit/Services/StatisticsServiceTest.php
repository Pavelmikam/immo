<?php

namespace Tests\Unit\Services;

use App\Models\Property;
use App\Models\PropertyView;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private StatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatisticsService();
    }

    private function makeProprietaire(): User
    {
        return User::factory()->create([
            'role'              => 'proprietaire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
    }

    private function makeLocataire(): User
    {
        return User::factory()->create([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
    }

    /** @test */
    public function test_getPropertyStats_compte_vues_sur_periode(): void
    {
        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->active()->create();

        PropertyView::factory()->count(3)->for($property)->create([
            'viewed_at' => now()->subDays(5),
        ]);
        PropertyView::factory()->count(2)->for($property)->create([
            'viewed_at' => now()->subDays(10),
        ]);

        $stats = $this->service->getPropertyStats($property, '7days');

        $this->assertEquals(3, $stats['views']['total']);
    }

    /** @test */
    public function test_getPropertyStats_conversion_rate_correct(): void
    {
        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->active()->create();
        $tenant   = $this->makeLocataire();

        PropertyView::factory()->count(50)->for($property)->create([
            'viewed_at' => now()->subDay(),
        ]);
        RentalRequest::factory()->count(5)->for($property)->create([
            'tenant_id'  => $tenant->id,
            'created_at' => now()->subDay(),
        ]);

        $stats = $this->service->getPropertyStats($property, '30days');

        $this->assertEquals(10.0, $stats['conversion_rate']);
    }

    /** @test */
    public function test_getPropertyStats_conversion_zero_si_pas_de_vues(): void
    {
        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->active()->create();

        $stats = $this->service->getPropertyStats($property, '30days');

        $this->assertEquals(0.0, $stats['conversion_rate']);
    }

    /** @test */
    public function test_getOwnerDashboard_agrege_toutes_annonces(): void
    {
        $owner = $this->makeProprietaire();

        Property::factory()->count(2)->for($owner, 'owner')->active()->create();
        Property::factory()->for($owner, 'owner')->create(['status' => 'draft']);

        $dashboard = $this->service->getOwnerDashboard($owner, '30days');

        $this->assertEquals(3, $dashboard['properties']['total']);
        $this->assertEquals(2, $dashboard['properties']['active']);
        $this->assertEquals(1, $dashboard['properties']['draft']);
    }

    /** @test */
    public function test_getOwnerDashboard_sans_annonce_ne_plante_pas(): void
    {
        $owner = $this->makeProprietaire();

        $dashboard = $this->service->getOwnerDashboard($owner, '30days');

        $this->assertEquals(0, $dashboard['views_total']);
        $this->assertEquals(0, $dashboard['requests_total']);
    }

    /** @test */
    public function test_getAdminAdvancedStats_top_cities_triees(): void
    {
        Property::factory()->count(5)->active()->create(['city' => 'Yaoundé']);
        Property::factory()->count(3)->active()->create(['city' => 'Douala']);

        $stats = $this->service->getAdminAdvancedStats('30days');

        $this->assertEquals('Yaoundé', $stats['top_cities'][0]['city']);
    }

    /** @test */
    public function test_getPeriodStart_retourne_bonne_date(): void
    {
        $start7   = $this->service->getPeriodStart('7days');
        $start1y  = $this->service->getPeriodStart('1year');

        $this->assertEqualsWithDelta(
            now()->subDays(7)->timestamp,
            $start7->timestamp,
            2
        );
        $this->assertEqualsWithDelta(
            now()->subYear()->timestamp,
            $start1y->timestamp,
            2
        );
    }
}
