<?php

namespace Database\Factories;

use App\Models\Bien;
use Illuminate\Database\Eloquent\Factories\Factory;

class BienFactory extends Factory
{
    protected $model = Bien::class;

    public function definition(): array
    {
        $quartiers = ['Plateau', 'Cocody', 'Marcory', 'Yopougon', 'Adjamé',
                      'Treichville', 'Deux Plateaux', 'Angré', 'Bingerville', 'Port-Bouët'];

        $villes = ['Abidjan', 'Yamoussoukro', 'Bouaké', 'San-Pédro', 'Daloa'];

        $statut = fake()->randomElement(['vente', 'location']);

        $prixBase = $statut === 'vente'
            ? fake()->numberBetween(15_000_000, 300_000_000)
            : fake()->numberBetween(50_000, 800_000);

        return [
            'titre'          => fake('fr_FR')->sentence(5),
            'description'    => fake('fr_FR')->paragraphs(2, true),
            'prix'           => $prixBase,
            'surface'        => fake()->randomFloat(2, 20, 300),
            'nb_pieces'      => fake()->numberBetween(1, 8),
            'nb_chambres'    => fake()->numberBetween(0, 5),
            'nb_salles_bain' => fake()->numberBetween(1, 3),
            'adresse'        => fake('fr_FR')->streetAddress(),
            'quartier'       => fake()->randomElement($quartiers),
            'ville'          => fake()->randomElement($villes),
            'code_postal'    => fake()->numerify('#####'),
            'statut'         => $statut,
            'disponible'     => fake()->boolean(80),
        ];
    }

    public function vente(): static
    {
        return $this->state(['statut' => 'vente']);
    }

    public function location(): static
    {
        return $this->state(['statut' => 'location']);
    }

    public function disponible(): static
    {
        return $this->state(['disponible' => true]);
    }
}
