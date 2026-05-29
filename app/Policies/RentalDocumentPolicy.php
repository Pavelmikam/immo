<?php

namespace App\Policies;

use App\Models\RentalDocument;
use App\Models\User;

class RentalDocumentPolicy
{
    public function view(User $user, RentalDocument $document): bool
    {
        return $user->id === $document->uploaded_by
            || $document->rentalRequest->property->isOwnedBy($user)
            || $user->isAdmin();
    }

    public function delete(User $user, RentalDocument $document): bool
    {
        return $user->id === $document->uploaded_by
            && $document->rentalRequest->status === 'en_attente';
    }

    public function verify(User $user, RentalDocument $document): bool
    {
        return $user->isAdmin()
            || $document->rentalRequest->property->isOwnedBy($user);
    }
}
