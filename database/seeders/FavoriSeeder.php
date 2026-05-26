<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\Favori;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::where('role', 'client')->get();
        $biens   = Bien::where('disponible', true)->get();

        if ($clients->isEmpty() || $biens->isEmpty()) {
            return;
        }

        $clients->each(function (User $client) use ($biens) {
            $biens->random(min(rand(2, 5), $biens->count()))->each(function (Bien $bien) use ($client) {
                Favori::firstOrCreate([
                    'user_id' => $client->id,
                    'bien_id' => $bien->id,
                ]);
            });
        });
    }
}
