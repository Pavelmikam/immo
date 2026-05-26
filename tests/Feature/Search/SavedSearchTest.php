<?php

namespace Tests\Feature\Search;

use App\Models\Property;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProperties;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase, CreatesProperties;

    private function makeCriteria(array $overrides = []): array
    {
        return array_merge(['city' => 'Yaoundé', 'type' => 'studio'], $overrides);
    }

    public function test_locataire_peut_sauvegarder_une_recherche(): void
    {
        $user  = User::factory()->locataire()->create();
        $token = $this->tokenFor($user);

        $response = $this->withToken($token)->postJson('/api/saved-searches', [
            'name'     => 'Studio Bastos',
            'criteria' => $this->makeCriteria(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $user->id,
            'name'    => 'Studio Bastos',
        ]);
    }

    public function test_peut_lister_ses_recherches_sauvegardees(): void
    {
        $user  = User::factory()->locataire()->create();
        $token = $this->tokenFor($user);
        SavedSearch::factory()->for($user)->count(3)->create();

        $this->withToken($token)->getJson('/api/saved-searches')
             ->assertStatus(200)
             ->assertJsonCount(3, 'data');
    }

    public function test_ne_voit_pas_recherches_des_autres(): void
    {
        $userA = User::factory()->locataire()->create();
        $userB = User::factory()->locataire()->create();
        SavedSearch::factory()->for($userA)->count(2)->create();
        SavedSearch::factory()->for($userB)->count(3)->create();

        $tokenA = $this->tokenFor($userA);

        $this->withToken($tokenA)->getJson('/api/saved-searches')
             ->assertStatus(200)
             ->assertJsonCount(2, 'data');
    }

    public function test_peut_mettre_a_jour_recherche(): void
    {
        $user   = User::factory()->locataire()->create();
        $token  = $this->tokenFor($user);
        $search = SavedSearch::factory()->for($user)->create(['name' => 'Ancien nom']);

        $this->withToken($token)->putJson("/api/saved-searches/{$search->id}", [
            'name' => 'Nouveau nom',
        ])->assertStatus(200)
          ->assertJsonPath('name', 'Nouveau nom');
    }

    public function test_peut_supprimer_recherche(): void
    {
        $user   = User::factory()->locataire()->create();
        $token  = $this->tokenFor($user);
        $search = SavedSearch::factory()->for($user)->create();

        $this->withToken($token)->deleteJson("/api/saved-searches/{$search->id}")
             ->assertStatus(204);

        $this->assertDatabaseMissing('saved_searches', ['id' => $search->id]);
    }

    public function test_limite_10_recherches_par_utilisateur(): void
    {
        $user  = User::factory()->locataire()->create();
        $token = $this->tokenFor($user);
        SavedSearch::factory()->for($user)->count(10)->create();

        $this->withToken($token)->postJson('/api/saved-searches', [
            'name'     => 'Onzième recherche',
            'criteria' => $this->makeCriteria(),
        ])->assertStatus(422)
          ->assertJsonPath('code', 'SAVED_SEARCH_LIMIT_REACHED');
    }

    public function test_peut_toggle_notifications(): void
    {
        $user   = User::factory()->locataire()->create();
        $token  = $this->tokenFor($user);
        $search = SavedSearch::factory()->for($user)->create(['notifications_enabled' => true]);

        $response = $this->withToken($token)
                         ->patchJson("/api/saved-searches/{$search->id}/toggle-notifications");

        $this->assertFalse($response->json('notifications_enabled'));

        $response2 = $this->withToken($token)
                          ->patchJson("/api/saved-searches/{$search->id}/toggle-notifications");

        $this->assertTrue($response2->json('notifications_enabled'));
    }

    public function test_peut_executer_recherche_sauvegardee(): void
    {
        $owner = $this->makeProprietaire();
        Property::factory()->for($owner, 'owner')->active()->count(2)->create(['city' => 'Yaoundé']);
        Property::factory()->for($owner, 'owner')->active()->count(2)->create(['city' => 'Douala']);

        $user   = User::factory()->locataire()->create();
        $token  = $this->tokenFor($user);
        $search = SavedSearch::factory()->for($user)->create(['criteria' => ['city' => 'Yaoundé']]);

        $response = $this->withToken($token)->getJson("/api/saved-searches/{$search->id}/results");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_ne_peut_pas_voir_recherche_dautrui(): void
    {
        $userA  = User::factory()->locataire()->create();
        $userB  = User::factory()->locataire()->create();
        $search = SavedSearch::factory()->for($userB)->create();
        $token  = $this->tokenFor($userA);

        $this->withToken($token)->getJson("/api/saved-searches/{$search->id}")
             ->assertStatus(404);
    }

    public function test_non_connecte_ne_peut_pas_acceder_recherches(): void
    {
        $this->getJson('/api/saved-searches')->assertStatus(401);
    }
}
