<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return null;
    }

    public function view(?User $user, Property $property): bool
    {
        // active and sous_reservation are publicly visible
        if ($property->isActive() || $property->status === 'sous_reservation') {
            return true;
        }
        if ($user === null) {
            return false;
        }
        return $property->isOwner($user);
    }

    public function create(User $user): bool
    {
        return $user->isProprietaire();
    }

    public function update(User $user, Property $property): bool
    {
        return $property->isOwner($user)
            && ($property->isDraft() || $property->isRejected());
    }

    public function submit(User $user, Property $property): bool
    {
        return $property->isOwner($user)
            && ($property->isDraft() || $property->isRejected())
            && $property->images()->count() > 0;
    }

    public function delete(User $user, Property $property): bool
    {
        return $property->isOwner($user)
            && !$property->isActive();
    }

    public function uploadImage(User $user, Property $property): bool
    {
        return $property->isOwner($user)
            && ($property->isDraft() || $property->isRejected());
    }

    public function deleteImage(User $user, Property $property): bool
    {
        return $property->isOwner($user)
            && ($property->isDraft() || $property->isRejected());
    }

    public function reorderImages(User $user, Property $property): bool
    {
        return $property->isOwner($user);
    }

    public function moderate(User $user, Property $property): bool
    {
        return $user->isAdmin();
    }

    public function archive(User $user, Property $property): bool
    {
        return $property->isOwner($user) && $property->isActive();
    }
}
