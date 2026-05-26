<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => 'password',
            'role'              => fake()->randomElement(['locataire', 'proprietaire']),
            'phone'             => null,
            'city'              => null,
            'bio'               => null,
            'is_active'         => true,
            'remember_token'    => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function locataire(): static
    {
        return $this->state(['role' => 'locataire']);
    }

    public function proprietaire(): static
    {
        return $this->state(['role' => 'proprietaire']);
    }

    public function suspended(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
