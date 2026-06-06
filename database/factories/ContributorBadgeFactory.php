<?php

namespace Database\Factories;

use App\Models\ContributorBadge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContributorBadgeFactory extends Factory
{
    protected $model = ContributorBadge::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'badge'      => fake()->randomElement([
                'premier_signalement', 'contributeur_actif', 'expert_quartier',
                'explorateur', 'fiable',
            ]),
            'awarded_at' => now(),
        ];
    }
}
