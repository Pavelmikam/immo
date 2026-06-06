<?php

namespace Tests\Unit\Services;

use App\Contracts\NeighborhoodScoreServiceInterface;
use App\Models\NeighborhoodReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeighborhoodScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): NeighborhoodScoreServiceInterface
    {
        return app(NeighborhoodScoreServiceInterface::class);
    }

    private function makeUser(): User
    {
        return User::factory()->create(['role' => 'locataire', 'is_active' => true]);
    }

    public function test_canSubmitReport_retourne_true_si_pas_de_rapport_recent(): void
    {
        $user   = $this->makeUser();
        $result = $this->service()->canSubmitReport($user, 'eau', 3.8667, 11.5167);

        $this->assertTrue($result);
    }

    public function test_canSubmitReport_retourne_false_si_rapport_existant_meme_zone(): void
    {
        $user = $this->makeUser();

        NeighborhoodReport::factory()->create([
            'user_id'   => $user->id,
            'criterion' => 'eau',
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
        ]);

        $result = $this->service()->canSubmitReport($user, 'eau', 3.8667, 11.5167);

        $this->assertFalse($result);
    }

    public function test_canSubmitReport_retourne_true_si_rapport_trop_vieux(): void
    {
        $user   = $this->makeUser();
        $report = NeighborhoodReport::factory()->create([
            'user_id'   => $user->id,
            'criterion' => 'eau',
            'latitude'  => 3.8667,
            'longitude' => 11.5167,
        ]);

        \DB::table('neighborhood_reports')
           ->where('id', $report->id)
           ->update(['created_at' => now()->subDays(31)]);

        $result = $this->service()->canSubmitReport($user, 'eau', 3.8667, 11.5167);

        $this->assertTrue($result);
    }

    public function test_computeScore_cree_entree_neighborhood_score(): void
    {
        $users = User::factory()->count(3)->create(['role' => 'locataire', 'is_active' => true]);

        foreach ($users as $user) {
            NeighborhoodReport::factory()->create([
                'user_id'      => $user->id,
                'criterion'    => 'eau',
                'city'         => 'Yaoundé',
                'neighborhood' => 'Bastos',
                'latitude'     => 3.8800,
                'longitude'    => 11.5200,
                'is_validated' => true,
                'is_flagged'   => false,
            ]);
        }

        $this->service()->computeScore('Yaoundé', 'Bastos');

        $this->assertDatabaseHas('neighborhood_scores', [
            'city'      => 'Yaoundé',
            'criterion' => 'eau',
        ]);
    }

    public function test_computeScore_calcule_moyenne_correcte(): void
    {
        $users  = User::factory()->count(3)->create(['role' => 'locataire', 'is_active' => true]);
        $scores = [2, 4, 3];

        foreach ($users as $i => $user) {
            NeighborhoodReport::factory()->create([
                'user_id'      => $user->id,
                'criterion'    => 'eau',
                'city'         => 'Yaoundé',
                'neighborhood' => 'Bastos',
                'score'        => $scores[$i],
                'is_validated' => true,
                'is_flagged'   => false,
            ]);
        }

        $result = $this->service()->computeScore('Yaoundé', 'Bastos');

        $eauScore = $result->firstWhere('criterion', 'eau');
        $this->assertEquals(3.0, (float) $eauScore->average_score);
    }

    public function test_computeScore_exclut_rapports_flagues(): void
    {
        $users = User::factory()->count(3)->create(['role' => 'locataire', 'is_active' => true]);

        NeighborhoodReport::factory()->create([
            'user_id'      => $users[0]->id,
            'criterion'    => 'eau',
            'city'         => 'Yaoundé',
            'neighborhood' => 'Bastos',
            'score'        => 4,
            'is_validated' => true,
            'is_flagged'   => false,
        ]);
        NeighborhoodReport::factory()->create([
            'user_id'      => $users[1]->id,
            'criterion'    => 'eau',
            'city'         => 'Yaoundé',
            'neighborhood' => 'Bastos',
            'score'        => 4,
            'is_validated' => true,
            'is_flagged'   => false,
        ]);
        NeighborhoodReport::factory()->flagged()->create([
            'user_id'      => $users[2]->id,
            'criterion'    => 'eau',
            'city'         => 'Yaoundé',
            'neighborhood' => 'Bastos',
            'score'        => 1,
        ]);

        $result   = $this->service()->computeScore('Yaoundé', 'Bastos');
        $eauScore = $result->firstWhere('criterion', 'eau');

        $this->assertEquals(4.0, (float) $eauScore->average_score);
    }

    public function test_computeScore_ignore_rapports_plus_vieux_3_mois(): void
    {
        $user = User::factory()->create(['role' => 'locataire', 'is_active' => true]);

        $recent = NeighborhoodReport::factory()->create([
            'user_id'      => $user->id,
            'criterion'    => 'eau',
            'city'         => 'Yaoundé',
            'neighborhood' => 'Bastos',
            'score'        => 4,
            'is_validated' => true,
            'is_flagged'   => false,
        ]);

        $user2 = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        $old   = NeighborhoodReport::factory()->create([
            'user_id'      => $user2->id,
            'criterion'    => 'eau',
            'city'         => 'Yaoundé',
            'neighborhood' => 'Bastos',
            'score'        => 1,
            'is_validated' => true,
            'is_flagged'   => false,
        ]);

        \DB::table('neighborhood_reports')
           ->where('id', $old->id)
           ->update(['created_at' => now()->subMonths(4)]);

        $result   = $this->service()->computeScore('Yaoundé', 'Bastos');
        $eauScore = $result->firstWhere('criterion', 'eau');

        $this->assertEquals(4.0, (float) $eauScore->average_score);
    }

    public function test_getScoreHistory_retourne_6_mois(): void
    {
        $history = $this->service()->getScoreHistory('Yaoundé', 'Bastos', 'eau');

        $this->assertCount(6, $history);
    }

    public function test_checkAndAwardBadges_attribue_correctement(): void
    {
        $user = $this->makeUser();

        NeighborhoodReport::factory()->create([
            'user_id'      => $user->id,
            'is_validated' => true,
        ]);

        $badges = $this->service()->checkAndAwardBadges($user);

        $this->assertContains('premier_signalement', $badges);

        // 10 rapports total
        NeighborhoodReport::factory()->count(9)->create([
            'user_id'      => $user->id,
            'is_validated' => true,
        ]);

        $badges = $this->service()->checkAndAwardBadges($user);
        $this->assertContains('contributeur_actif', $badges);
    }
}
