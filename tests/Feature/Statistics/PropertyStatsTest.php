<?php

namespace Tests\Feature\Statistics;

use App\Models\Property;
use App\Models\PropertyView;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class PropertyStatsTest extends TestCase
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

    private function makeAdmin(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'admin',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $attrs));
    }

    /** @test */
    public function test_proprietaire_peut_voir_stats_annonce(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $token    = $this->tokenFor($owner);

        $response = $this->withToken($token)
            ->getJson("/api/statistics/property/{$property->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['views', 'requests', 'conversion_rate', 'favorites_count'],
            ]);
    }

    /** @test */
    public function test_locataire_ne_peut_pas_voir_stats_annonce(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeLocataire();
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)
            ->getJson("/api/statistics/property/{$property->id}")
            ->assertStatus(403);
    }

    /** @test */
    public function test_admin_peut_voir_stats_de_nimporte_quelle_annonce(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $admin    = $this->makeAdmin();
        $token    = $this->tokenFor($admin);

        $this->withToken($token)
            ->getJson("/api/statistics/property/{$property->id}")
            ->assertStatus(200);
    }

    /** @test */
    public function test_stats_filtrees_par_periode_7days(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $token    = $this->tokenFor($owner);

        PropertyView::factory()->for($property)->create([
            'viewed_at' => now()->subDays(10),
        ]);
        PropertyView::factory()->for($property)->create([
            'viewed_at' => now()->subDays(5),
        ]);

        $response = $this->withToken($token)
            ->getJson("/api/statistics/property/{$property->id}?period=7days");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.views.total'));
    }

    /** @test */
    public function test_periode_invalide_refusee(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $token    = $this->tokenFor($owner);

        $this->withToken($token)
            ->getJson("/api/statistics/property/{$property->id}?period=invalid")
            ->assertStatus(422);
    }

    /** @test */
    public function test_conversion_rate_calcule_correctement(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $token    = $this->tokenFor($owner);

        PropertyView::factory()->count(100)->for($property)->create([
            'viewed_at' => now()->subDay(),
        ]);

        $tenant = $this->makeLocataire();
        RentalRequest::factory()->count(10)->for($property)->create([
            'tenant_id'  => $tenant->id,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->withToken($token)
            ->getJson("/api/statistics/property/{$property->id}?period=30days");

        $response->assertStatus(200);
        $this->assertEquals(10.0, $response->json('data.conversion_rate'));
    }

    /** @test */
    public function test_conversion_rate_zero_si_aucune_vue(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $token    = $this->tokenFor($owner);

        $response = $this->withToken($token)
            ->getJson("/api/statistics/property/{$property->id}");

        $response->assertStatus(200);
        $this->assertEquals(0.0, $response->json('data.conversion_rate'));
    }

    /** @test */
    public function test_vues_par_jour_dans_stats(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $token    = $this->tokenFor($owner);

        PropertyView::factory()->for($property)->create(['viewed_at' => now()->subDays(2)]);
        PropertyView::factory()->for($property)->create(['viewed_at' => now()->subDays(1)]);
        PropertyView::factory()->for($property)->create(['viewed_at' => now()]);

        $response = $this->withToken($token)
            ->getJson("/api/statistics/property/{$property->id}?period=7days");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.views.by_day'));
    }
}
