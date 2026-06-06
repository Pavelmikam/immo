<?php

namespace Tests\Feature\Neighborhood;

use App\Models\NeighborhoodReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesNeighborhoodData;

class ContributorBadgeTest extends TestCase
{
    use RefreshDatabase, CreatesNeighborhoodData;

    private function makeUser(): User
    {
        return User::factory()->create([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
    }

    public function test_premier_badge_attribue_au_premier_rapport(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/neighborhood/report', [
            'criterion' => 'eau',
            'score'     => 4,
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
        ])->assertStatus(201);

        $this->assertDatabaseHas('contributor_badges', [
            'user_id' => $user->id,
            'badge'   => 'premier_signalement',
        ]);
    }

    public function test_badge_contributeur_actif_apres_10_rapports(): void
    {
        $user    = $this->makeUser();
        $service = app(\App\Contracts\NeighborhoodScoreServiceInterface::class);

        // Créer 10 rapports directement (bypass anti-spam)
        for ($i = 0; $i < 10; $i++) {
            NeighborhoodReport::factory()->create([
                'user_id'      => $user->id,
                'is_validated' => true,
            ]);
        }

        $service->checkAndAwardBadges($user);

        $this->assertDatabaseHas('contributor_badges', [
            'user_id' => $user->id,
            'badge'   => 'contributeur_actif',
        ]);
    }

    public function test_badge_explorateur_apres_3_quartiers(): void
    {
        $user    = $this->makeUser();
        $service = app(\App\Contracts\NeighborhoodScoreServiceInterface::class);

        $neighborhoods = ['Bastos', 'Mvan', 'Emana'];
        foreach ($neighborhoods as $n) {
            NeighborhoodReport::factory()->create([
                'user_id'      => $user->id,
                'is_validated' => true,
                'neighborhood' => $n,
            ]);
        }

        $service->checkAndAwardBadges($user);

        $this->assertDatabaseHas('contributor_badges', [
            'user_id' => $user->id,
            'badge'   => 'explorateur',
        ]);
    }

    public function test_badge_non_duplique(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        // Premier rapport
        $this->withToken($token)->postJson('/api/neighborhood/report', [
            'criterion' => 'eau',
            'score'     => 4,
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
        ])->assertStatus(201);

        // Deuxième rapport (critère différent)
        $this->withToken($token)->postJson('/api/neighborhood/report', [
            'criterion' => 'securite',
            'score'     => 3,
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
        ])->assertStatus(201);

        $count = \App\Models\ContributorBadge::where('user_id', $user->id)
                                             ->where('badge', 'premier_signalement')
                                             ->count();
        $this->assertEquals(1, $count);
    }

    public function test_profil_contributeur_retourne_points_et_badges(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        // Soumettre un rapport pour avoir des données
        $this->withToken($token)->postJson('/api/neighborhood/report', [
            'criterion' => 'eau',
            'score'     => 4,
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
        ]);

        $response = $this->withToken($token)->getJson('/api/neighborhood/my-profile');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['contributor_points', 'reports_count', 'badges']]);
    }

    public function test_points_incrementes_de_5_par_rapport(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/neighborhood/report', [
            'criterion' => 'eau',
            'score'     => 4,
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
        ])->assertStatus(201);

        $this->assertEquals(5, $user->fresh()->contributor_points);

        $this->withToken($token)->postJson('/api/neighborhood/report', [
            'criterion' => 'securite',
            'score'     => 3,
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
        ])->assertStatus(201);

        $this->assertEquals(10, $user->fresh()->contributor_points);
    }
}
