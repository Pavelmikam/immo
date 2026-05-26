<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        $specialites = [
            'Appartements résidentiels',
            'Maisons et villas',
            'Immobilier commercial',
            'Chambres et studios',
            'Hôtels et établissements',
            'Terrains et lots',
        ];

        return [
            'telephone'  => fake('fr_FR')->phoneNumber(),
            'specialite' => fake()->randomElement($specialites),
            'biographie' => fake('fr_FR')->paragraph(3),
        ];
    }
}
