<?php

namespace Tests\Feature\Export;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class ExportCsvTest extends TestCase
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
    public function test_admin_peut_exporter_annonces_en_xlsx(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        Property::factory()->count(3)->active()->create();

        $this->withToken($token)
            ->getJson('/api/admin/export/properties')
            ->assertStatus(200);
    }

    /** @test */
    public function test_export_annonces_format_csv(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->withToken($token)
            ->getJson('/api/admin/export/properties?format=csv')
            ->assertStatus(200);
    }

    /** @test */
    public function test_admin_peut_exporter_utilisateurs(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->withToken($token)
            ->getJson('/api/admin/export/users')
            ->assertStatus(200);
    }

    /** @test */
    public function test_export_utilisateurs_filtres_par_role(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        User::factory()->count(3)->create([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);

        $this->withToken($token)
            ->getJson('/api/admin/export/users?role=locataire')
            ->assertStatus(200);
    }

    /** @test */
    public function test_admin_peut_exporter_demandes(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->withToken($token)
            ->getJson('/api/admin/export/rental-requests')
            ->assertStatus(200);
    }

    /** @test */
    public function test_export_cree_log_admin(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->withToken($token)
            ->getJson('/api/admin/export/properties');

        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $admin->id,
            'action'   => 'export.properties',
        ]);
    }

    /** @test */
    public function test_non_admin_ne_peut_pas_exporter(): void
    {
        $tenant = $this->makeLocataire();
        $token  = $this->tokenFor($tenant);

        $this->withToken($token)
            ->getJson('/api/admin/export/properties')
            ->assertStatus(403);
    }
}
