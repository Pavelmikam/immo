<?php

namespace Tests\Feature\Statistics;

use App\Models\Property;
use App\Models\PropertyView;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class AdminStatisticsTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role'              => 'admin',
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
    public function test_admin_peut_voir_stats_avancees(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->withToken($token)
            ->getJson('/api/admin/statistics/advanced')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['top_cities', 'top_types', 'acceptance_rate'],
            ]);
    }

    /** @test */
    public function test_top_cities_triees_par_count(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        Property::factory()->count(5)->active()->create(['city' => 'Yaoundé']);
        Property::factory()->count(2)->active()->create(['city' => 'Douala']);

        $response = $this->withToken($token)
            ->getJson('/api/admin/statistics/advanced');

        $response->assertStatus(200);
        $this->assertEquals('Yaoundé', $response->json('data.top_cities.0.city'));
    }

    /** @test */
    public function test_acceptance_rate_calcule_correctement(): void
    {
        $admin  = $this->makeAdmin();
        $token  = $this->tokenFor($admin);
        $owner  = $this->makeProprietaire();
        $tenant = $this->makeLocataire();
        $property = $this->createApprovedProperty($owner);

        RentalRequest::factory()->count(8)->for($property)->create([
            'tenant_id' => $tenant->id,
            'status'    => 'acceptee',
        ]);
        RentalRequest::factory()->count(2)->for($property)->create([
            'tenant_id' => $tenant->id,
            'status'    => 'refusee',
        ]);

        $response = $this->withToken($token)
            ->getJson('/api/admin/statistics/advanced');

        $response->assertStatus(200);
        $this->assertEquals(80.0, $response->json('data.acceptance_rate'));
    }

    /** @test */
    public function test_acceptance_rate_zero_si_aucune_decision(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $response = $this->withToken($token)
            ->getJson('/api/admin/statistics/advanced');

        $response->assertStatus(200);
        $this->assertEquals(0.0, $response->json('data.acceptance_rate'));
    }

    /** @test */
    public function test_timeline_vues_retourne_donnees(): void
    {
        $admin    = $this->makeAdmin();
        $token    = $this->tokenFor($admin);
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);

        PropertyView::factory()->for($property)->create(['viewed_at' => now()->subDay()]);

        $this->withToken($token)
            ->getJson('/api/admin/statistics/views-timeline')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    ['date', 'total_views', 'unique_views'],
                ],
            ]);
    }

    /** @test */
    public function test_top_properties_retourne_max_20(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        Property::factory()->count(25)->active()->create();

        $response = $this->withToken($token)
            ->getJson('/api/admin/statistics/top-properties');

        $response->assertStatus(200);
        $this->assertCount(20, $response->json('data'));
    }

    /** @test */
    public function test_metric_invalide_refuse(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->withToken($token)
            ->getJson('/api/admin/statistics/top-properties?metric=invalid')
            ->assertStatus(422);
    }

    /** @test */
    public function test_non_admin_ne_peut_pas_voir_stats_admin(): void
    {
        $tenant = $this->makeLocataire();
        $token  = $this->tokenFor($tenant);

        $this->withToken($token)
            ->getJson('/api/admin/statistics/advanced')
            ->assertStatus(403);
    }
}
