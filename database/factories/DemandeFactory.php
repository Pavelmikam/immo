<?php

namespace Database\Factories;

use App\Models\Demande;
use Illuminate\Database\Eloquent\Factories\Factory;

class DemandeFactory extends Factory
{
    protected $model = Demande::class;

    public function definition(): array
    {
        return [
            'nom'       => fake('fr_FR')->name(),
            'email'     => fake()->safeEmail(),
            'telephone' => fake('fr_FR')->phoneNumber(),
            'message'   => fake('fr_FR')->paragraph(2),
            'statut'    => fake()->randomElement(['en_attente', 'en_cours', 'traitee']),
        ];
    }

    public function enAttente(): static
    {
        return $this->state(['statut' => 'en_attente']);
    }
}
