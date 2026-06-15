<?php

namespace App\Services;

use App\Contracts\DocumentServiceInterface;
use App\Contracts\RentalRequestServiceInterface;
use App\Models\Property;
use App\Models\RentalDocument;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RentalRequestService implements RentalRequestServiceInterface
{
    public function __construct(private DocumentServiceInterface $documentService) {}

    public function createRequest(User $tenant, Property $property, array $data): RentalRequest
    {
        if (!$property->isAvailable()) {
            throw new \DomainException('Ce bien n\'est pas disponible à la location.');
        }

        if ($property->isOwnedBy($tenant)) {
            throw new \DomainException('Vous ne pouvez pas postuler sur votre propre bien.');
        }

        if ($tenant->hasActiveDemandFor($property->id)) {
            throw new \DomainException('Vous avez déjà une demande en cours pour ce bien.');
        }

        return DB::transaction(function () use ($tenant, $property, $data) {
            $rentalRequest = RentalRequest::create([
                'property_id' => $property->id,
                'tenant_id'   => $tenant->id,
                'status'      => 'en_attente',
                'message'     => $data['message'] ?? null,
            ]);

            Property::withoutTimestamps(fn () => $property->increment('requests_count'));

            $rentalRequest = $rentalRequest->load(['property', 'tenant', 'documents']);
            event(new \App\Events\RentalRequestCreated($rentalRequest));

            return $rentalRequest;
        });
    }

    public function acceptRequest(RentalRequest $request, User $owner, ?string $ownerResponse = null): RentalRequest
    {
        if (!$request->canBeDecidedBy($owner)) {
            throw new \DomainException('Vous ne pouvez pas accepter cette demande.');
        }

        return DB::transaction(function () use ($request, $ownerResponse) {
            $request->update([
                'status'         => 'acceptee',
                'owner_response' => $ownerResponse,
                'decided_at'     => now(),
            ]);

            RentalRequest::where('property_id', $request->property_id)
                         ->where('id', '!=', $request->id)
                         ->where('status', 'en_attente')
                         ->update([
                             'status'         => 'refusee',
                             'owner_response' => 'Un autre locataire a été sélectionné.',
                             'decided_at'     => now(),
                         ]);

            Property::withoutTimestamps(
                fn () => $request->property->update(['status' => 'sous_reservation'])
            );

            $result = $request->fresh()->load(['property', 'tenant', 'documents']);
            event(new \App\Events\RentalRequestAccepted($result));

            return $result;
        });
    }

    public function refuseRequest(RentalRequest $request, User $owner, ?string $ownerResponse = null): RentalRequest
    {
        if (!$request->canBeDecidedBy($owner)) {
            throw new \DomainException('Vous ne pouvez pas refuser cette demande.');
        }

        $request->update([
            'status'         => 'refusee',
            'owner_response' => $ownerResponse,
            'decided_at'     => now(),
        ]);

        $result = $request->fresh()->load(['property', 'tenant', 'documents']);
        event(new \App\Events\RentalRequestRefused($result));

        return $result;
    }

    public function cancelRequest(RentalRequest $request, User $tenant): RentalRequest
    {
        if (!$request->canBeCancelledBy($tenant)) {
            throw new \DomainException('Vous ne pouvez pas annuler cette demande.');
        }

        $request->update(['status' => 'annulee']);

        return $request->fresh();
    }

    public function addDocument(
        RentalRequest $request,
        User $uploader,
        UploadedFile $file,
        string $type,
        ?string $description = null
    ): RentalDocument {
        if ($uploader->id !== $request->tenant_id) {
            throw new \DomainException('Seul le locataire peut ajouter des documents.');
        }

        $existing = $request->documents()->where('type', $type)->first();
        if ($existing) {
            $this->documentService->deleteDocument($existing->file_path);
            $existing->delete();
        }

        $paths    = $this->documentService->storeDocument($file, $request->id, $type);
        $document = RentalDocument::create([
            ...$paths,
            'rental_request_id' => $request->id,
            'uploaded_by'       => $uploader->id,
            'type'              => $type,
            'description'       => $description,
        ]);

        $this->updateDossierComplete($request);

        return $document;
    }

    public function deleteDocument(RentalDocument $document, User $user): void
    {
        if ($document->uploaded_by !== $user->id
            || $document->rentalRequest->status !== 'en_attente') {
            throw new \DomainException('Vous ne pouvez pas supprimer ce document.');
        }

        $this->documentService->deleteDocument($document->file_path);
        $document->delete();
        $this->updateDossierComplete($document->rentalRequest);
    }

    public function verifyDocument(RentalDocument $document, User $verifier): RentalDocument
    {
        $document->update([
            'is_verified' => true,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);

        return $document->fresh();
    }

    private function updateDossierComplete(RentalRequest $request): void
    {
        $recommendedTypes = ['cni', 'bulletin_salaire'];
        $uploadedTypes    = $request->documents()->pluck('type')->toArray();
        $hasAllRequired   = empty(array_diff($recommendedTypes, $uploadedTypes));

        $request->update(['dossier_complete' => $hasAllRequired]);
    }
}
