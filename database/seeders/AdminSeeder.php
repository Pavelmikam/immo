<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@immoconnect.cm'],
            [
                'name'              => 'Administrateur ImmoConnect',
                'password'          => 'Admin@1234',
                'role'              => 'admin',
                'phone'             => '+237600000000',
                'city'              => 'Yaoundé',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
