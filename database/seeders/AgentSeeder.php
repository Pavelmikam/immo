<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $agentsFixe = [
            [
                'email'      => 'agent1@immoconnect.ci',
                'telephone'  => '+225 07 00 11 22',
                'specialite' => 'Appartements résidentiels',
                'biographie' => 'Spécialiste des appartements à Cocody et Plateau depuis 2015. Accompagnement personnalisé de chaque client.',
            ],
            [
                'email'      => 'agent2@immoconnect.ci',
                'telephone'  => '+225 05 33 44 55',
                'specialite' => 'Maisons et villas',
                'biographie' => 'Expert en villas et maisons familiales sur Abidjan et ses environs. Plus de 200 transactions réalisées.',
            ],
            [
                'email'      => 'agent3@immoconnect.ci',
                'telephone'  => '+225 01 66 77 88',
                'specialite' => 'Hôtels et immobilier commercial',
                'biographie' => 'Dédié à l\'immobilier commercial et hôtelier en Côte d\'Ivoire depuis 2010.',
            ],
            [
                'email'      => 'agent4@immoconnect.ci',
                'telephone'  => '+225 07 99 00 11',
                'specialite' => 'Chambres et studios meublés',
                'biographie' => 'Spécialiste de la location meublée courte et longue durée à Yopougon et Adjamé.',
            ],
            [
                'email'      => 'agent5@immoconnect.ci',
                'telephone'  => '+225 05 22 33 44',
                'specialite' => 'Immobilier résidentiel et locatif',
                'biographie' => 'Conseiller immobilier certifié, opérant sur l\'ensemble du territoire ivoirien.',
            ],
        ];

        foreach ($agentsFixe as $agentData) {
            $user = User::where('email', $agentData['email'])->first();
            if ($user && ! $user->agent) {
                Agent::create([
                    'user_id'    => $user->id,
                    'telephone'  => $agentData['telephone'],
                    'specialite' => $agentData['specialite'],
                    'biographie' => $agentData['biographie'],
                ]);
            }
        }

        // Profils agents pour les agents aléatoires sans profil
        User::where('role', 'agent')
            ->whereDoesntHave('agent')
            ->each(function (User $user) {
                Agent::factory()->create(['user_id' => $user->id]);
            });
    }
}
