<?php

namespace Tests\Feature\Export;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class ExportPdfTest extends TestCase
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
    public function test_admin_peut_telecharger_rapport_activite_pdf(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $response = $this->withToken($token)
            ->getJson('/api/admin/export/activity-report');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function test_proprietaire_peut_telecharger_rapport_annonce_pdf(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $admin    = $this->makeAdmin();
        $token    = $this->tokenFor($admin);

        $response = $this->withToken($token)
            ->getJson("/api/admin/export/property-report/{$property->id}");

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function test_locataire_ne_peut_pas_telecharger_rapport_annonce(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->createApprovedProperty($owner);
        $tenant   = $this->makeLocataire();
        $token    = $this->tokenFor($tenant);

        $this->withToken($token)
            ->getJson("/api/admin/export/property-report/{$property->id}")
            ->assertStatus(403);
    }

    /** @test */
    public function test_rapport_pdf_periode_1year(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $response = $this->withToken($token)
            ->getJson('/api/admin/export/activity-report?period=1year');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}
