<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentalRequestFactory extends Factory
{
    protected $model = RentalRequest::class;

    public function definition(): array
    {
        return [
            'property_id'      => Property::factory(),
            'tenant_id'        => User::factory()->create(['role' => 'locataire', 'email_verified_at' => now()])->id,
            'status'           => 'en_attente',
            'message'          => fake()->paragraph(),
            'owner_response'   => null,
            'decided_at'       => null,
            'dossier_complete' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'en_attente']);
    }

    public function accepted(): static
    {
        return $this->state([
            'status'         => 'acceptee',
            'decided_at'     => now(),
            'owner_response' => 'Bienvenue !',
        ]);
    }

    public function refused(): static
    {
        return $this->state([
            'status'         => 'refusee',
            'decided_at'     => now(),
            'owner_response' => 'Profil non retenu.',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'annulee']);
    }
}
