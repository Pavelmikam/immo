<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['nom' => 'Abidjan',       'code' => 'AB'],
            ['nom' => 'Yamoussoukro',  'code' => 'YK'],
            ['nom' => 'Bouaké',        'code' => 'BK'],
            ['nom' => 'San-Pédro',     'code' => 'SP'],
            ['nom' => 'Daloa',         'code' => 'DL'],
        ];

        foreach ($regions as $region) {
            Region::firstOrCreate(['nom' => $region['nom']], $region);
        }
    }
}
