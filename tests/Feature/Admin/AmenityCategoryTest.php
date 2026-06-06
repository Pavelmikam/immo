<?php

namespace Tests\Feature\Admin;

use App\Models\AmenityCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmenityCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role'              => 'admin',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
    }

    public function test_admin_peut_lister_toutes_les_categories(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        AmenityCategory::factory()->count(3)->create();

        $this->withToken($token)->getJson('/api/admin/amenity-categories')
             ->assertStatus(200);
    }

    public function test_filtre_par_category(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        AmenityCategory::factory()->count(2)->create(['category' => 'amenity']);
        AmenityCategory::factory()->create(['category' => 'charge']);

        $response = $this->withToken($token)->getJson('/api/admin/amenity-categories?category=amenity');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_peut_creer_nouvelle_categorie(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->withToken($token)->postJson('/api/admin/amenity-categories', [
            'category' => 'amenity',
            'value'    => 'piscine',
            'label'    => 'Piscine',
        ])->assertStatus(201);

        $this->assertDatabaseHas('amenity_categories', ['value' => 'piscine']);
    }

    public function test_valeur_dupliquee_refusee(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        AmenityCategory::factory()->create(['category' => 'amenity', 'value' => 'piscine']);

        $this->withToken($token)->postJson('/api/admin/amenity-categories', [
            'category' => 'amenity',
            'value'    => 'piscine',
            'label'    => 'Piscine',
        ])->assertStatus(422);
    }

    public function test_admin_peut_modifier_categorie(): void
    {
        $admin    = $this->makeAdmin();
        $token    = $this->tokenFor($admin);
        $category = AmenityCategory::factory()->create(['label' => 'Piscine']);

        $this->withToken($token)->putJson("/api/admin/amenity-categories/{$category->id}", [
            'label' => 'Piscine privée',
        ])->assertStatus(200);

        $this->assertDatabaseHas('amenity_categories', [
            'id'    => $category->id,
            'label' => 'Piscine privée',
        ]);
    }

    public function test_admin_peut_desactiver_categorie(): void
    {
        $admin    = $this->makeAdmin();
        $token    = $this->tokenFor($admin);
        $category = AmenityCategory::factory()->create(['is_active' => true]);

        $this->withToken($token)->deleteJson("/api/admin/amenity-categories/{$category->id}")
             ->assertStatus(204);

        $this->assertDatabaseHas('amenity_categories', [
            'id'        => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_endpoint_public_retourne_seulement_actives(): void
    {
        AmenityCategory::factory()->create(['category' => 'amenity', 'value' => 'piscine',  'is_active' => true]);
        AmenityCategory::factory()->create(['category' => 'amenity', 'value' => 'jacuzzi',  'is_active' => false]);

        $response = $this->getJson('/api/reference/amenities');

        $response->assertStatus(200);
        $amenities = collect($response->json('amenities'))->pluck('value')->toArray();
        $this->assertContains('piscine', $amenities);
        $this->assertNotContains('jacuzzi', $amenities);
    }

    public function test_non_admin_ne_peut_pas_modifier_categories(): void
    {
        $tenant   = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        $token    = $this->tokenFor($tenant);
        $category = AmenityCategory::factory()->create();

        $this->withToken($token)->putJson("/api/admin/amenity-categories/{$category->id}", [
            'label' => 'Nouveau label',
        ])->assertStatus(403);
    }
}
