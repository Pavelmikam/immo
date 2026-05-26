<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\Demande;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemandeSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::where('role', 'client')->get();
        $biens   = Bien::where('disponible', true)->get();

        if ($clients->isEmpty() || $biens->isEmpty()) {
            return;
        }

        // Demandes anonymes (sans user_id)
        $biens->random(min(5, $biens->count()))->each(function (Bien $bien) {
            Demande::factory()->enAttente()->create([
                'bien_id' => $bien->id,
                'user_id' => null,
            ]);
        });

        // Demandes de clients connectés
        $clients->each(function (User $client) use ($biens) {
            $biens->random(min(3, $biens->count()))->each(function (Bien $bien) use ($client) {
                Demande::firstOrCreate(
                    ['user_id' => $client->id, 'bien_id' => $bien->id],
                    [
                        'nom'       => $client->name,
                        'email'     => $client->email,
                        'telephone' => fake('fr_FR')->phoneNumber(),
                        'message'   => fake('fr_FR')->paragraph(2),
                        'statut'    => fake()->randomElement(['en_attente', 'en_attente', 'en_cours', 'traitee']),
                    ]
                );
            });
        });
    }
}
