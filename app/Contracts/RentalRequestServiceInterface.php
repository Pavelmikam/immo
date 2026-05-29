<?php

namespace App\Contracts;

use App\Models\Property;
use App\Models\RentalDocument;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface RentalRequestServiceInterface
{
    public function createRequest(User $tenant, Property $property, array $data): RentalRequest;

    public function acceptRequest(RentalRequest $request, User $owner, ?string $ownerResponse = null): RentalRequest;

    public function refuseRequest(RentalRequest $request, User $owner, string $ownerResponse): RentalRequest;

    public function cancelRequest(RentalRequest $request, User $tenant): RentalRequest;

    public function addDocument(
        RentalRequest $request,
        User $uploader,
        UploadedFile $file,
        string $type,
        ?string $description = null
    ): RentalDocument;

    public function deleteDocument(RentalDocument $document, User $user): void;

    public function verifyDocument(RentalDocument $document, User $verifier): RentalDocument;
}
