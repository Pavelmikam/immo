<?php

namespace Database\Factories;

use App\Models\NeighborhoodScore;
use Illuminate\Database\Eloquent\Factories\Factory;

class NeighborhoodScoreFactory extends Factory
{
    protected $model = NeighborhoodScore::class;

    public function definition(): array
    {
        return [
            'city'             => 'Yaoundé',
            'neighborhood'     => 'Bastos',
            'center_latitude'  => 3.8800,
            'center_longitude' => 11.5200,
            'criterion'        => 'eau',
            'average_score'    => fake()->randomFloat(2, 1.5, 4.8),
            'global_score'     => fake()->randomFloat(2, 1.5, 4.8),
            'report_count'     => fake()->numberBetween(5, 50),
            'unique_reporters' => fake()->numberBetween(3, 20),
            'period_start'     => now()->subMonths(3)->toDateString(),
            'period_end'       => now()->toDateString(),
            'computed_at'      => now(),
        ];
    }
}
