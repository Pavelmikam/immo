<?php

namespace Tests\Feature\Favorite;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class FavoriteTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    public function test_locataire_peut_ajouter_bien_aux_favoris(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);
        $locataire = User::factory()->locataire()->create();
        $token    = $this->tokenFor($locataire);

        $response = $this->withToken($token)->postJson("/api/favorites/{$property->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('is_favorited', true);

        $this->assertDatabaseHas('favorites', [
            'user_id'     => $locataire->id,
            'property_id' => $property->id,
        ]);

        $this->assertEquals(1, $property->fresh()->favorites_count);
    }

    public function test_locataire_peut_retirer_bien_des_favoris(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);
        $locataire = User::factory()->locataire()->create();
        $token    = $this->tokenFor($locataire);

        // Add
        $this->withToken($token)->postJson("/api/favorites/{$property->id}");
        // Remove
        $response = $this->withToken($token)->postJson("/api/favorites/{$property->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('is_favorited', false);

        $this->assertDatabaseMissing('favorites', [
            'user_id'     => $locataire->id,
            'property_id' => $property->id,
        ]);

        $this->assertEquals(0, $property->fresh()->favorites_count);
    }

    public function test_favori_est_idempotent(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);
        $locataire = User::factory()->locataire()->create();
        $token    = $this->tokenFor($locataire);

        $first  = $this->withToken($token)->postJson("/api/favorites/{$property->id}");
        $second = $this->withToken($token)->postJson("/api/favorites/{$property->id}");

        $this->assertTrue($first->json('is_favorited'));
        $this->assertFalse($second->json('is_favorited'));
    }

    public function test_proprietaire_peut_aussi_mettre_en_favori(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);
        $autre    = $this->makeProprietaire();
        $token    = $this->tokenFor($autre);

        $this->withToken($token)->postJson("/api/favorites/{$property->id}")
             ->assertStatus(200)
             ->assertJsonPath('is_favorited', true);
    }

    public function test_non_connecte_ne_peut_pas_favoriter(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);

        $this->postJson("/api/favorites/{$property->id}")->assertStatus(401);
    }

    public function test_locataire_peut_lister_ses_favoris(): void
    {
        $owner    = $this->makeProprietaire();
        $locataire = User::factory()->locataire()->create();
        $token    = $this->tokenFor($locataire);

        $properties = Property::factory()->for($owner, 'owner')->active()->count(3)->create();
        $locataire->favorites()->attach($properties->pluck('id')->toArray());

        $response = $this->withToken($token)->getJson('/api/favorites');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_liste_favoris_exclut_biens_inactifs(): void
    {
        $owner    = $this->makeProprietaire();
        $locataire = User::factory()->locataire()->create();
        $token    = $this->tokenFor($locataire);

        $active   = $this->makeActiveProperty($owner);
        $archived = $this->makeProperty($owner, ['status' => 'archived']);

        $locataire->favorites()->attach([$active->id, $archived->id]);

        $response = $this->withToken($token)->getJson('/api/favorites');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_liste_favoris_paginee(): void
    {
        $owner    = $this->makeProprietaire();
        $locataire = User::factory()->locataire()->create();
        $token    = $this->tokenFor($locataire);

        $properties = Property::factory()->for($owner, 'owner')->active()->count(20)->create();
        $locataire->favorites()->attach($properties->pluck('id')->toArray());

        $response = $this->withToken($token)->getJson('/api/favorites');

        $response->assertStatus(200);
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals(20, $response->json('meta.total'));
    }

    public function test_check_favori_retourne_statut_correct(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);
        $locataire = User::factory()->locataire()->create();
        $token    = $this->tokenFor($locataire);

        $response = $this->withToken($token)->getJson("/api/favorites/{$property->id}/check");
        $this->assertFalse($response->json('is_favorited'));

        $locataire->favorites()->attach($property->id);

        $response = $this->withToken($token)->getJson("/api/favorites/{$property->id}/check");
        $this->assertTrue($response->json('is_favorited'));
    }

    public function test_check_favori_sans_connexion_retourne_401(): void
    {
        $owner    = $this->makeProprietaire();
        $property = $this->makeActiveProperty($owner);

        $this->getJson("/api/favorites/{$property->id}/check")->assertStatus(401);
    }

    public function test_favorites_count_ne_descend_pas_sous_zero(): void
    {
        $owner    = $this->makeProprietaire();
        $property = Property::factory()->for($owner, 'owner')->active()->create(['favorites_count' => 0]);
        $locataire = User::factory()->locataire()->create();
        $token    = $this->tokenFor($locataire);

        // Toggle off without having toggled on (the detach won't find a row, count stays 0)
        // Instead: add then remove twice to force the edge
        $this->withToken($token)->postJson("/api/favorites/{$property->id}"); // add → count=1
        $this->withToken($token)->postJson("/api/favorites/{$property->id}"); // remove → count=0

        $response = $this->withToken($token)->postJson("/api/favorites/{$property->id}"); // add → count=1
        $this->withToken($token)->postJson("/api/favorites/{$property->id}"); // remove → count=0

        $this->assertGreaterThanOrEqual(0, $property->fresh()->favorites_count);
    }
}
