<?php

namespace Tests\Feature\Statistics;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class DashboardTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    private function makeLocataire(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $attrs));
    }

    /** @test */
    public function test_proprietaire_peut_voir_son_dashboard(): void
    {
        $owner = $this->makeProprietaire();
        $token = $this->tokenFor($owner);

        $this->withToken($token)
            ->getJson('/api/statistics/owner-dashboard')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'properties', 'views_total', 'requests_total',
                    'potential_revenue', 'top_properties',
                ],
            ]);
    }

    /** @test */
    public function test_locataire_ne_peut_pas_voir_dashboard_proprietaire(): void
    {
        $tenant = $this->makeLocataire();
        $token  = $this->tokenFor($tenant);

        $this->withToken($token)
            ->getJson('/api/statistics/owner-dashboard')
            ->assertStatus(403);
    }

    /** @test */
    public function test_locataire_peut_voir_son_dashboard(): void
    {
        $tenant = $this->makeLocataire();
        $token  = $this->tokenFor($tenant);

        $this->withToken($token)
            ->getJson('/api/statistics/tenant-dashboard')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'requests', 'favorites_count', 'contributor_points', 'badges',
                ],
            ]);
    }

    /** @test */
    public function test_proprietaire_ne_peut_pas_voir_dashboard_locataire(): void
    {
        $owner = $this->makeProprietaire();
        $token = $this->tokenFor($owner);

        $this->withToken($token)
            ->getJson('/api/statistics/tenant-dashboard')
            ->assertStatus(403);
    }

    /** @test */
    public function test_dashboard_proprietaire_inclut_top_5_annonces(): void
    {
        $owner = $this->makeProprietaire();
        $token = $this->tokenFor($owner);

        Property::factory()->count(7)->for($owner, 'owner')->active()->create();

        $response = $this->withToken($token)
            ->getJson('/api/statistics/owner-dashboard');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data.top_properties'));
    }

    /** @test */
    public function test_endpoint_popular_properties_public(): void
    {
        Property::factory()->count(15)->active()->create();

        $response = $this->getJson('/api/properties/popular');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertLessThanOrEqual(10, count($data));

        if (count($data) > 1) {
            $this->assertGreaterThanOrEqual(
                $data[count($data) - 1]['views_count'] ?? 0,
                $data[0]['views_count'] ?? 0
            );
        }
    }

    /** @test */
    public function test_dashboard_proprietaire_sans_annonce(): void
    {
        $owner = $this->makeProprietaire();
        $token = $this->tokenFor($owner);

        $response = $this->withToken($token)
            ->getJson('/api/statistics/owner-dashboard');

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.views_total'));
        $this->assertEquals(0, $response->json('data.requests_total'));
    }
}
