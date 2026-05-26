<?php

namespace Database\Seeders;

use App\Models\TypeBien;
use Illuminate\Database\Seeder;

class TypeBienSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Appartement', 'description' => 'Logement dans un immeuble collectif ou résidence.'],
            ['nom' => 'Maison',      'description' => 'Habitation individuelle avec ou sans jardin.'],
            ['nom' => 'Chambre',     'description' => 'Pièce meublée à louer, souvent en colocation.'],
            ['nom' => 'Hôtel',       'description' => 'Établissement d\'hébergement à usage commercial.'],
        ];

        foreach ($types as $type) {
            TypeBien::firstOrCreate(['nom' => $type['nom']], $type);
        }
    }
}
