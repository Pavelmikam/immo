<?php

namespace Tests\Feature\Neighborhood;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesNeighborhoodData;

class SubmitReportTest extends TestCase
{
    use RefreshDatabase, CreatesNeighborhoodData;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $attrs));
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'criterion' => 'eau',
            'score'     => 4,
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
        ], $overrides);
    }

    public function test_utilisateur_peut_soumettre_une_evaluation(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        $response = $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('neighborhood_reports', [
            'user_id'   => $user->id,
            'criterion' => 'eau',
            'score'     => 4,
        ]);

        $user->refresh();
        $this->assertEquals(5, $user->contributor_points);
    }

    public function test_non_authentifie_ne_peut_pas_soumettre(): void
    {
        $this->postJson('/api/neighborhood/report', $this->basePayload())
             ->assertStatus(401);
    }

    public function test_score_hors_plage_refuse(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload(['score' => 6]))
             ->assertStatus(422);

        $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload(['score' => 0]))
             ->assertStatus(422);
    }

    public function test_critere_invalide_refuse(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload(['criterion' => 'piscine']))
             ->assertStatus(422);
    }

    public function test_latitude_obligatoire(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        $payload = $this->basePayload();
        unset($payload['latitude']);

        $this->withToken($token)->postJson('/api/neighborhood/report', $payload)
             ->assertStatus(422)->assertJsonValidationErrors(['latitude']);
    }

    public function test_anti_spam_bloque_double_soumission_meme_zone(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload())
             ->assertStatus(201);

        // Même zone (1km de distance)
        $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload([
            'latitude'  => 3.8668,
            'longitude' => 11.5168,
        ]))->assertStatus(422);
    }

    public function test_critere_different_autorise_dans_meme_zone(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload(['criterion' => 'eau']))
             ->assertStatus(201);

        $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload(['criterion' => 'securite']))
             ->assertStatus(201);
    }

    public function test_zone_differente_autorise_meme_critere(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        // Yaoundé
        $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload([
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
            'city'      => 'Yaoundé',
        ]))->assertStatus(201);

        // Douala (>100km)
        $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload([
            'latitude'  => 4.0500,
            'longitude' => 9.7000,
            'city'      => 'Douala',
        ]))->assertStatus(201);
    }

    public function test_premier_signalement_attribue_badge(): void
    {
        $user  = $this->makeUser();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/neighborhood/report', $this->basePayload())
             ->assertStatus(201);

        $this->assertDatabaseHas('contributor_badges', [
            'user_id' => $user->id,
            'badge'   => 'premier_signalement',
        ]);
    }
}
