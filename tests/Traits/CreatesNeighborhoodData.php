<?php

namespace Tests\Traits;

use App\Models\NeighborhoodReport;
use App\Models\NeighborhoodScore;
use App\Models\User;

trait CreatesNeighborhoodData
{
    protected function createNeighborhoodReport(
        ?User $user = null,
        array $attrs = []
    ): NeighborhoodReport {
        $user = $user ?? User::factory()->create([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);

        return NeighborhoodReport::factory()->create(array_merge(
            ['user_id' => $user->id],
            $attrs
        ));
    }

    protected function createNeighborhoodScore(
        string $city = 'Yaoundé',
        ?string $neighborhood = 'Bastos',
        string $criterion = 'eau',
        float $score = 3.5
    ): NeighborhoodScore {
        return NeighborhoodScore::factory()->create([
            'city'          => $city,
            'neighborhood'  => $neighborhood,
            'criterion'     => $criterion,
            'average_score' => $score,
        ]);
    }

    protected function createReportsForZone(
        string $city = 'Yaoundé',
        ?string $neighborhood = 'Bastos',
        int $count = 5
    ): void {
        $users = User::factory()->count($count)->create([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);

        foreach ($users as $i => $user) {
            NeighborhoodReport::factory()->create([
                'user_id'      => $user->id,
                'criterion'    => 'eau',
                'city'         => $city,
                'neighborhood' => $neighborhood,
                'score'        => ($i % 5) + 1,
            ]);
        }
    }
}
