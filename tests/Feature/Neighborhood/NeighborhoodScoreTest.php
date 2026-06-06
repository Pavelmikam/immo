<?php

namespace Tests\Feature\Neighborhood;

use App\Models\NeighborhoodReport;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesNeighborhoodData;
use Tests\Traits\CreatesProperties;

class NeighborhoodScoreTest extends TestCase
{
    use RefreshDatabase, CreatesNeighborhoodData, CreatesProperties;

    public function test_endpoint_score_retourne_donnees_pour_localisation(): void
    {
        $this->createReportsForZone('Yaoundé', 'Bastos', 5);

        $response = $this->getJson('/api/neighborhood/score?latitude=3.8667&longitude=11.5167');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data'));
        $response->assertJsonStructure(['data' => ['global_score', 'criteria', 'report_count']]);
    }

    public function test_endpoint_score_retourne_null_si_aucun_rapport(): void
    {
        $response = $this->getJson('/api/neighborhood/score?latitude=3.8667&longitude=11.5167');

        $response->assertStatus(200);
        $this->assertNull($response->json('data'));
    }

    public function test_score_exclut_rapports_plus_vieux_que_3_mois(): void
    {
        $user   = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        $report = NeighborhoodReport::factory()->create([
            'user_id'   => $user->id,
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'score'     => 5,
        ]);

        \DB::table('neighborhood_reports')
            ->where('id', $report->id)
            ->update(['created_at' => now()->subMonths(4)]);

        $response = $this->getJson('/api/neighborhood/score?latitude=3.8667&longitude=11.5167');

        $response->assertStatus(200);
        $this->assertNull($response->json('data'));
    }

    public function test_score_exclut_rapports_flagues(): void
    {
        $user = User::factory()->create(['role' => 'locataire', 'is_active' => true]);

        NeighborhoodReport::factory()->create([
            'user_id'   => $user->id,
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'score'     => 4,
            'is_validated' => true,
            'is_flagged'   => false,
        ]);

        $user2 = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        NeighborhoodReport::factory()->flagged()->create([
            'user_id'   => $user2->id,
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'score'     => 1,
        ]);

        $response = $this->getJson('/api/neighborhood/score?latitude=3.8667&longitude=11.5167');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data'));
        $this->assertEquals(1, $response->json('data.report_count'));
    }

    public function test_historique_retourne_6_mois(): void
    {
        $response = $this->getJson('/api/neighborhood/history?city=Yaoundé&neighborhood=Bastos&criterion=eau');

        $response->assertStatus(200);
        $this->assertCount(6, $response->json('data'));

        $response->assertJsonStructure([
            'data' => [['month', 'average_score', 'label']],
        ]);
    }

    public function test_score_affiche_sur_bien_avec_coordonnees(): void
    {
        $this->createReportsForZone('Yaoundé', 'Bastos', 5);

        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->active()->create([
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
            'district'  => 'Bastos',
        ]);

        // Force score cache
        $service = app(\App\Contracts\NeighborhoodScoreServiceInterface::class);
        $service->computeScore('Yaoundé', 'Bastos');

        $response = $this->getJson("/api/neighborhood/property/{$property->id}");

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data'));
    }

    public function test_score_null_sur_bien_sans_coordonnees(): void
    {
        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->active()->create([
            'latitude'  => null,
            'longitude' => null,
        ]);

        $response = $this->getJson("/api/neighborhood/property/{$property->id}");

        $response->assertStatus(200);
        $this->assertNull($response->json('data'));
    }

    public function test_radius_km_personnalise_accepte(): void
    {
        $response = $this->getJson('/api/neighborhood/score?latitude=3.8667&longitude=11.5167&radius_km=5');

        $response->assertStatus(200);
    }

    public function test_endpoint_score_public_sans_auth(): void
    {
        $response = $this->getJson('/api/neighborhood/score?latitude=3.8667&longitude=11.5167');

        $this->assertContains($response->status(), [200]);
    }
}
