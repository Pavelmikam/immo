<?php

namespace Database\Factories;

use App\Models\NeighborhoodReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NeighborhoodReportFactory extends Factory
{
    protected $model = NeighborhoodReport::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'criterion'    => fake()->randomElement(['eau', 'electricite', 'securite', 'transport', 'commerces']),
            'score'        => fake()->numberBetween(1, 5),
            'latitude'     => fake()->randomElement([3.8667, 3.8800, 3.8500]),
            'longitude'    => fake()->randomElement([11.5167, 11.5000, 11.5300]),
            'city'         => 'Yaoundé',
            'neighborhood' => fake()->randomElement(['Bastos', 'Mvan', 'Biyem-Assi', 'Nlongkak', 'Emana']),
            'is_validated' => true,
            'is_flagged'   => false,
        ];
    }

    public function flagged(): static
    {
        return $this->state([
            'is_flagged'   => true,
            'is_validated' => false,
        ]);
    }

    public function forDouala(): static
    {
        return $this->state([
            'city'      => 'Douala',
            'latitude'  => 4.0500,
            'longitude' => 9.7000,
            'neighborhood' => fake()->randomElement(['Akwa', 'Bonanjo', 'Deido']),
        ]);
    }
}
