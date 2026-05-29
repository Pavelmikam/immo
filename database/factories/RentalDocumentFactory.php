<?php

namespace Database\Factories;

use App\Models\RentalDocument;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentalDocumentFactory extends Factory
{
    protected $model = RentalDocument::class;

    public function definition(): array
    {
        $types = ['cni', 'passeport', 'bulletin_salaire', 'attestation_travail', 'releve_bancaire'];
        $type  = fake()->randomElement($types);

        return [
            'rental_request_id' => RentalRequest::factory(),
            'uploaded_by'       => User::factory()->create(['role' => 'locataire'])->id,
            'type'              => $type,
            'file_path'         => "documents/1/{$type}/{$type}_test.pdf",
            'original_name'     => strtoupper($type) . '.pdf',
            'mime_type'         => 'application/pdf',
            'file_size'         => 512000,
            'is_verified'       => false,
        ];
    }

    public function verified(): static
    {
        return $this->state([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }
}
