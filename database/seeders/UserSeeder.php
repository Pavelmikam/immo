<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Administrateur
        User::firstOrCreate(['email' => 'admin@immoconnect.ci'], [
            'name'     => 'Admin ImmoConnect',
            'password' => bcrypt('password'),
            'role'     => 'admin',
        ]);

        // Agents avec emails fixes pour les tests
        $agents = [
            ['name' => 'Kouassi Yao',    'email' => 'agent1@immoconnect.ci'],
            ['name' => 'Adjoua Bamba',   'email' => 'agent2@immoconnect.ci'],
            ['name' => 'Konan Koffi',    'email' => 'agent3@immoconnect.ci'],
            ['name' => 'Aïssatou Diallo','email' => 'agent4@immoconnect.ci'],
            ['name' => 'Eugène Traoré',  'email' => 'agent5@immoconnect.ci'],
        ];

        foreach ($agents as $agent) {
            User::firstOrCreate(['email' => $agent['email']], [
                'name'     => $agent['name'],
                'password' => bcrypt('password'),
                'role'     => 'agent',
            ]);
        }

        // Clients avec emails fixes pour les tests
        $clients = [
            ['name' => 'Fatou Coulibaly', 'email' => 'client1@immoconnect.ci'],
            ['name' => 'Jean-Marc Assi',  'email' => 'client2@immoconnect.ci'],
            ['name' => 'Mariam Touré',    'email' => 'client3@immoconnect.ci'],
            ['name' => 'David Kouamé',    'email' => 'client4@immoconnect.ci'],
            ['name' => 'Carine Gbagbo',   'email' => 'client5@immoconnect.ci'],
        ];

        foreach ($clients as $client) {
            User::firstOrCreate(['email' => $client['email']], [
                'name'     => $client['name'],
                'password' => bcrypt('password'),
                'role'     => 'client',
            ]);
        }

        // Agents et clients supplémentaires aléatoires
        User::factory()->agent()->count(5)->create();
        User::factory()->client()->count(10)->create();
    }
}
