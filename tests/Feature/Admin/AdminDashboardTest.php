<?php

namespace Tests\Feature\Admin;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class AdminDashboardTest extends TestCase
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

    public function test_admin_peut_acceder_au_tableau_de_bord(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $response = $this->withToken($token)->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'users', 'properties', 'rental_requests',
                     'conversations', 'reports', 'generated_at',
                 ]);
    }

    public function test_dashboard_retourne_comptes_corrects(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        User::factory()->count(3)->create(['role' => 'locataire', 'is_active' => true]);
        User::factory()->count(2)->create(['role' => 'proprietaire', 'is_active' => true]);

        $response = $this->withToken($token)->getJson('/api/admin/dashboard');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('users.locataires'));
        $this->assertEquals(2, $response->json('users.proprietaires'));
    }

    public function test_dashboard_compte_properties_par_statut(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);
        $owner = $this->makeProprietaire();

        Property::factory()->for($owner, 'owner')->active()->count(2)->create();
        Property::factory()->for($owner, 'owner')->pending()->count(1)->create();
        Property::factory()->for($owner, 'owner')->create(['status' => 'draft']);

        $response = $this->withToken($token)->getJson('/api/admin/dashboard');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('properties.active'));
        $this->assertEquals(1, $response->json('properties.pending'));
        $this->assertEquals(1, $response->json('properties.draft'));
    }

    public function test_non_admin_ne_peut_pas_acceder(): void
    {
        $tenant = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        $token  = $this->tokenFor($tenant);

        $this->withToken($token)->getJson('/api/admin/dashboard')->assertStatus(403);
    }

    public function test_non_authentifie_ne_peut_pas_acceder(): void
    {
        $this->getJson('/api/admin/dashboard')->assertStatus(401);
    }
}
