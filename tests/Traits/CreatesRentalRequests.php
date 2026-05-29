<?php

namespace Tests\Traits;

use App\Models\Property;
use App\Models\RentalDocument;
use App\Models\RentalRequest;
use App\Models\User;

trait CreatesRentalRequests
{
    protected function makeTenant(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $attrs));
    }

    protected function createRentalRequest(
        ?User $tenant = null,
        ?Property $property = null,
        array $attrs = []
    ): RentalRequest {
        $tenant   = $tenant   ?? $this->makeTenant();
        $property = $property ?? $this->createApprovedProperty();

        return RentalRequest::factory()
                            ->for($property)
                            ->create(array_merge(['tenant_id' => $tenant->id], $attrs));
    }

    protected function createRentalRequestWithDocuments(
        ?User $tenant = null,
        int $docCount = 2
    ): RentalRequest {
        $request = $this->createRentalRequest($tenant);

        for ($i = 0; $i < $docCount; $i++) {
            RentalDocument::factory()->create([
                'rental_request_id' => $request->id,
                'uploaded_by'       => $request->tenant_id,
            ]);
        }

        return $request->load('documents');
    }

    protected function createApprovedProperty(?User $owner = null, array $attrs = []): Property
    {
        $owner = $owner ?? $this->makeProprietaire();
        return Property::factory()->for($owner, 'owner')->active()->create($attrs);
    }
}
