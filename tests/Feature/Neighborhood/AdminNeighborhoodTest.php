<?php

namespace Tests\Feature\Neighborhood;

use App\Models\NeighborhoodReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesNeighborhoodData;

class AdminNeighborhoodTest extends TestCase
{
    use RefreshDatabase, CreatesNeighborhoodData;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role'              => 'admin',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
    }

    public function test_admin_peut_lister_les_rapports(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->createNeighborhoodReport();
        $this->createNeighborhoodReport();

        $this->withToken($token)->getJson('/api/admin/neighborhood/reports')
             ->assertStatus(200);
    }

    public function test_admin_peut_filtrer_par_criterion(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->createNeighborhoodReport(null, ['criterion' => 'eau']);
        $this->createNeighborhoodReport(null, ['criterion' => 'eau']);
        $this->createNeighborhoodReport(null, ['criterion' => 'eau']);
        $this->createNeighborhoodReport(null, ['criterion' => 'securite']);
        $this->createNeighborhoodReport(null, ['criterion' => 'securite']);

        $response = $this->withToken($token)->getJson('/api/admin/neighborhood/reports?criterion=eau');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_peut_flaguer_rapport_suspect(): void
    {
        $admin  = $this->makeAdmin();
        $token  = $this->tokenFor($admin);
        $report = $this->createNeighborhoodReport();

        $this->withToken($token)->postJson("/api/admin/neighborhood/reports/{$report->id}/flag")
             ->assertStatus(200);

        $this->assertDatabaseHas('neighborhood_reports', [
            'id'           => $report->id,
            'is_flagged'   => true,
            'is_validated' => false,
        ]);

        $this->assertDatabaseHas('admin_logs', [
            'action'   => 'neighborhood_report.flag',
            'admin_id' => $admin->id,
        ]);
    }

    public function test_admin_peut_revalider_rapport_flague(): void
    {
        $admin  = $this->makeAdmin();
        $token  = $this->tokenFor($admin);
        $report = $this->createNeighborhoodReport(null, [
            'is_flagged'   => true,
            'is_validated' => false,
        ]);

        $this->withToken($token)->postJson("/api/admin/neighborhood/reports/{$report->id}/validate")
             ->assertStatus(200);

        $this->assertDatabaseHas('neighborhood_reports', [
            'id'           => $report->id,
            'is_validated' => true,
            'is_flagged'   => false,
        ]);
    }

    public function test_admin_peut_forcer_recalcul_scores(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->createReportsForZone('Yaoundé', 'Bastos', 3);

        $response = $this->withToken($token)->postJson('/api/admin/neighborhood/recompute', [
            'city' => 'Yaoundé',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('neighborhood_scores', ['city' => 'Yaoundé']);
    }

    public function test_non_admin_ne_peut_pas_acceder_admin_neighborhood(): void
    {
        $tenant = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        $token  = $this->tokenFor($tenant);
        $report = $this->createNeighborhoodReport();

        $this->withToken($token)->postJson("/api/admin/neighborhood/reports/{$report->id}/flag")
             ->assertStatus(403);
    }
}
