<?php

namespace App\Policies;

use App\Models\RentalRequest;
use App\Models\User;

class RentalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RentalRequest $request): bool
    {
        return $user->id === $request->tenant_id
            || $request->property->isOwnedBy($user)
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isLocataire() && $user->isEmailVerified();
    }

    public function accept(User $user, RentalRequest $request): bool
    {
        return $request->property->isOwnedBy($user);
    }

    public function refuse(User $user, RentalRequest $request): bool
    {
        return $request->property->isOwnedBy($user);
    }

    public function cancel(User $user, RentalRequest $request): bool
    {
        return $user->id === $request->tenant_id;
    }

    public function manageDocuments(User $user, RentalRequest $request): bool
    {
        return $user->id === $request->tenant_id || $user->isAdmin();
    }
}
