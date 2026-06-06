<?php

namespace Tests\Unit\Models;

use App\Models\NeighborhoodReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeighborhoodReportModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeReport(array $attrs = []): NeighborhoodReport
    {
        $user = User::factory()->create(['role' => 'locataire', 'is_active' => true]);

        return NeighborhoodReport::factory()->create(array_merge(
            ['user_id' => $user->id],
            $attrs
        ));
    }

    public function test_isGoodScore_retourne_true_si_score_4_ou_5(): void
    {
        $this->assertTrue($this->makeReport(['score' => 4])->isGoodScore());
        $this->assertTrue($this->makeReport(['score' => 5])->isGoodScore());
        $this->assertFalse($this->makeReport(['score' => 3])->isGoodScore());
    }

    public function test_isBadScore_retourne_true_si_score_1_ou_2(): void
    {
        $this->assertTrue($this->makeReport(['score' => 1])->isBadScore());
        $this->assertTrue($this->makeReport(['score' => 2])->isBadScore());
        $this->assertFalse($this->makeReport(['score' => 3])->isBadScore());
    }

    public function test_scope_recent_exclut_rapports_vieux(): void
    {
        $recent = $this->makeReport();
        $old    = $this->makeReport();

        \DB::table('neighborhood_reports')
           ->where('id', $old->id)
           ->update(['created_at' => now()->subMonths(4)]);

        $results = NeighborhoodReport::recent(3)->get();

        $this->assertTrue($results->contains('id', $recent->id));
        $this->assertFalse($results->contains('id', $old->id));
    }

    public function test_scope_validated_retourne_seulement_valides(): void
    {
        $valid   = $this->makeReport(['is_validated' => true]);
        $invalid = $this->makeReport(['is_validated' => false]);

        $results = NeighborhoodReport::validated()->get();

        $this->assertTrue($results->contains('id', $valid->id));
        $this->assertFalse($results->contains('id', $invalid->id));
    }

    public function test_scope_notFlagged_exclut_flagues(): void
    {
        $ok      = $this->makeReport(['is_flagged' => false]);
        $flagged = $this->makeReport(['is_flagged' => true]);

        $results = NeighborhoodReport::notFlagged()->get();

        $this->assertTrue($results->contains('id', $ok->id));
        $this->assertFalse($results->contains('id', $flagged->id));
    }

    public function test_scope_nearLocation_filtre_par_rayon(): void
    {
        // Rapport proche (Yaoundé-Centre, ~0.5km)
        $near = $this->makeReport(['latitude' => 3.8667, 'longitude' => 11.5167]);

        // Rapport éloigné (Douala, ~200km)
        $far = $this->makeReport(['latitude' => 4.0500, 'longitude' => 9.7000]);

        $results = NeighborhoodReport::nearLocation(3.8667, 11.5167, 2.0)->get();

        $this->assertTrue($results->contains('id', $near->id));
        $this->assertFalse($results->contains('id', $far->id));
    }
}
