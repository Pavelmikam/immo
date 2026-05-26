<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Ville;
use Illuminate\Database\Seeder;

class VilleSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Abidjan' => [
                ['nom' => 'Cocody',       'code_postal' => '01BP', 'latitude' => 5.3600,  'longitude' => -3.9732],
                ['nom' => 'Plateau',      'code_postal' => '01BP', 'latitude' => 5.3196,  'longitude' => -4.0228],
                ['nom' => 'Marcory',      'code_postal' => '01BP', 'latitude' => 5.3024,  'longitude' => -3.9986],
                ['nom' => 'Yopougon',     'code_postal' => '01BP', 'latitude' => 5.3472,  'longitude' => -4.0704],
                ['nom' => 'Adjamé',       'code_postal' => '01BP', 'latitude' => 5.3563,  'longitude' => -4.0168],
                ['nom' => 'Treichville',  'code_postal' => '01BP', 'latitude' => 5.2975,  'longitude' => -4.0097],
                ['nom' => 'Deux Plateaux','code_postal' => '01BP', 'latitude' => 5.3744,  'longitude' => -3.9829],
                ['nom' => 'Bingerville',  'code_postal' => '01BP', 'latitude' => 5.3583,  'longitude' => -3.8861],
            ],
            'Yamoussoukro' => [
                ['nom' => 'Yamoussoukro', 'code_postal' => '18BP', 'latitude' => 6.8276,  'longitude' => -5.2893],
            ],
            'Bouaké' => [
                ['nom' => 'Bouaké',       'code_postal' => '01BP', 'latitude' => 7.6894,  'longitude' => -5.0309],
                ['nom' => 'Koko',         'code_postal' => '01BP', 'latitude' => 7.7000,  'longitude' => -5.0400],
            ],
            'San-Pédro' => [
                ['nom' => 'San-Pédro',    'code_postal' => '15BP', 'latitude' => 4.7482,  'longitude' => -6.6363],
            ],
            'Daloa' => [
                ['nom' => 'Daloa',        'code_postal' => '19BP', 'latitude' => 6.8774,  'longitude' => -6.4502],
            ],
        ];

        foreach ($data as $regionNom => $villes) {
            $region = Region::where('nom', $regionNom)->first();
            if (! $region) continue;

            foreach ($villes as $ville) {
                Ville::firstOrCreate(
                    ['nom' => $ville['nom'], 'region_id' => $region->id],
                    array_merge($ville, ['region_id' => $region->id])
                );
            }
        }
    }
}
