<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Bien;
use App\Models\TypeBien;
use App\Models\Ville;
use Illuminate\Database\Seeder;

class BienSeeder extends Seeder
{
    public function run(): void
    {
        $agents    = Agent::with('user')->get();
        $typesBien = TypeBien::all()->keyBy('nom');
        $villes    = Ville::all();

        if ($agents->isEmpty() || $typesBien->isEmpty()) {
            return;
        }

        // Biens réalistes avec données fixes
        $biensFixe = [
            [
                'agent_email'  => 'agent1@immoconnect.ci',
                'type'         => 'Appartement',
                'titre'        => 'Appartement F3 vue mer à Cocody',
                'description'  => 'Magnifique appartement de 3 pièces avec vue dégagée, sécurisé 24h/24, parking inclus. Idéal pour famille ou investissement locatif.',
                'prix'         => 45_000_000,
                'surface'      => 85.0,
                'nb_pieces'    => 3,
                'nb_chambres'  => 2,
                'nb_salles_bain' => 1,
                'adresse'      => 'Rue des Jardins, Cocody',
                'quartier'     => 'Cocody',
                'ville'        => 'Abidjan',
                'statut'       => 'vente',
            ],
            [
                'agent_email'  => 'agent1@immoconnect.ci',
                'type'         => 'Appartement',
                'titre'        => 'Studio meublé au Plateau - courte durée',
                'description'  => 'Studio entièrement meublé et équipé, wifi inclus, idéal pour déplacements professionnels ou courts séjours.',
                'prix'         => 250_000,
                'surface'      => 35.0,
                'nb_pieces'    => 1,
                'nb_chambres'  => 1,
                'nb_salles_bain' => 1,
                'adresse'      => 'Avenue Chardy, Plateau',
                'quartier'     => 'Plateau',
                'ville'        => 'Abidjan',
                'statut'       => 'location',
            ],
            [
                'agent_email'  => 'agent2@immoconnect.ci',
                'type'         => 'Maison',
                'titre'        => 'Villa 5 chambres avec piscine - Deux Plateaux',
                'description'  => 'Superbe villa moderne de 5 chambres avec piscine privée, jardin paysager, double garage. Résidence fermée et sécurisée.',
                'prix'         => 180_000_000,
                'surface'      => 350.0,
                'nb_pieces'    => 7,
                'nb_chambres'  => 5,
                'nb_salles_bain' => 3,
                'adresse'      => 'Cité Les Lauriers, Deux Plateaux',
                'quartier'     => 'Deux Plateaux',
                'ville'        => 'Abidjan',
                'statut'       => 'vente',
            ],
            [
                'agent_email'  => 'agent2@immoconnect.ci',
                'type'         => 'Maison',
                'titre'        => 'Maison 3 chambres à louer à Marcory',
                'description'  => 'Belle maison individuelle de 3 chambres, salon spacieux, cuisine équipée, cour clôturée. Quartier calme et résidentiel.',
                'prix'         => 350_000,
                'surface'      => 120.0,
                'nb_pieces'    => 4,
                'nb_chambres'  => 3,
                'nb_salles_bain' => 2,
                'adresse'      => 'Rue du Commerce, Marcory',
                'quartier'     => 'Marcory',
                'ville'        => 'Abidjan',
                'statut'       => 'location',
            ],
            [
                'agent_email'  => 'agent3@immoconnect.ci',
                'type'         => 'Hôtel',
                'titre'        => 'Hôtel 20 chambres - Plateau centre affaires',
                'description'  => 'Établissement hôtelier de 20 chambres climatisées, restaurant, salle de conférence. Emplacement stratégique au coeur du quartier des affaires.',
                'prix'         => 850_000_000,
                'surface'      => 800.0,
                'nb_pieces'    => 25,
                'nb_chambres'  => 20,
                'nb_salles_bain' => 20,
                'adresse'      => 'Boulevard de la République, Plateau',
                'quartier'     => 'Plateau',
                'ville'        => 'Abidjan',
                'statut'       => 'vente',
            ],
            [
                'agent_email'  => 'agent4@immoconnect.ci',
                'type'         => 'Chambre',
                'titre'        => 'Chambre meublée en colocation - Yopougon',
                'description'  => 'Chambre individuelle meublée dans appartement partagé, accès cuisine et salon communs, wifi, eau et électricité inclus.',
                'prix'         => 60_000,
                'surface'      => 18.0,
                'nb_pieces'    => 1,
                'nb_chambres'  => 1,
                'nb_salles_bain' => 1,
                'adresse'      => 'Yopougon Selmer',
                'quartier'     => 'Yopougon',
                'ville'        => 'Abidjan',
                'statut'       => 'location',
            ],
            [
                'agent_email'  => 'agent4@immoconnect.ci',
                'type'         => 'Chambre',
                'titre'        => 'Chambre étudiante proche université - Adjamé',
                'description'  => 'Chambre sécurisée dans résidence étudiante, proche des transports et commerces. Gardien 24h/24.',
                'prix'         => 45_000,
                'surface'      => 15.0,
                'nb_pieces'    => 1,
                'nb_chambres'  => 1,
                'nb_salles_bain' => 1,
                'adresse'      => 'Cité universitaire, Adjamé',
                'quartier'     => 'Adjamé',
                'ville'        => 'Abidjan',
                'statut'       => 'location',
            ],
            [
                'agent_email'  => 'agent5@immoconnect.ci',
                'type'         => 'Appartement',
                'titre'        => 'Appartement F4 à Yamoussoukro',
                'description'  => 'Grand appartement de 4 pièces dans résidence calme de la capitale politique. Parking, eau et électricité.',
                'prix'         => 28_000_000,
                'surface'      => 110.0,
                'nb_pieces'    => 4,
                'nb_chambres'  => 3,
                'nb_salles_bain' => 2,
                'adresse'      => 'Avenue Félix Houphouët-Boigny',
                'quartier'     => 'Centre',
                'ville'        => 'Yamoussoukro',
                'statut'       => 'vente',
            ],
            [
                'agent_email'  => 'agent5@immoconnect.ci',
                'type'         => 'Maison',
                'titre'        => 'Maison à louer - Bouaké centre',
                'description'  => 'Maison de 4 chambres à louer, grande cour, quartier calme. Idéal famille ou professionnel.',
                'prix'         => 200_000,
                'surface'      => 150.0,
                'nb_pieces'    => 5,
                'nb_chambres'  => 4,
                'nb_salles_bain' => 2,
                'adresse'      => 'Rue du Marché, Bouaké',
                'quartier'     => 'Centre',
                'ville'        => 'Bouaké',
                'statut'       => 'location',
            ],
        ];

        foreach ($biensFixe as $bienData) {
            $agent = Agent::whereHas('user', fn ($q) => $q->where('email', $bienData['agent_email']))->first();
            $type  = $typesBien->get($bienData['type']);
            $ville = $villes->firstWhere('nom', $bienData['ville']);

            if (! $agent || ! $type) continue;

            Bien::firstOrCreate(
                ['titre' => $bienData['titre']],
                [
                    'description'    => $bienData['description'],
                    'prix'           => $bienData['prix'],
                    'surface'        => $bienData['surface'],
                    'nb_pieces'      => $bienData['nb_pieces'],
                    'nb_chambres'    => $bienData['nb_chambres'],
                    'nb_salles_bain' => $bienData['nb_salles_bain'],
                    'adresse'        => $bienData['adresse'],
                    'quartier'       => $bienData['quartier'],
                    'ville'          => $bienData['ville'],
                    'ville_id'       => optional($ville)->id,
                    'statut'         => $bienData['statut'],
                    'disponible'     => true,
                    'type_bien_id'   => $type->id,
                    'user_id'        => $agent->user_id,
                    'agent_id'       => $agent->id,
                ]
            );
        }

        // Biens aléatoires supplémentaires
        $allAgents = Agent::with('user')->get();
        $types     = TypeBien::all();

        $allAgents->each(function (Agent $agent) use ($types, $villes) {
            Bien::factory()
                ->count(rand(2, 4))
                ->create([
                    'user_id'      => $agent->user_id,
                    'agent_id'     => $agent->id,
                    'type_bien_id' => $types->random()->id,
                    'ville_id'     => $villes->isNotEmpty() ? $villes->random()->id : null,
                ]);
        });
    }
}
