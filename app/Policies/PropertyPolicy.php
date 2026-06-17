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
        // active, sous_reservation, loue: accessible via direct URL
        if ($property->isActive() || $property->isSousReservation() || $property->isLoue()) {
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
        return $property->isOwner($user) && $property->isDraft();
    }

    public function submit(User $user, Property $property): bool
    {
        return $property->isOwner($user)
            && $property->isDraft()
            && $property->images()->count() > 0;
    }

    public function delete(User $user, Property $property): bool
    {
        return $property->isOwner($user)
            && !$property->isActive();
    }

    public function uploadImage(User $user, Property $property): bool
    {
        return $property->isOwner($user) && $property->isDraft();
    }

    public function deleteImage(User $user, Property $property): bool
    {
        return $property->isOwner($user) && $property->isDraft();
    }

    public function reorderImages(User $user, Property $property): bool
    {
        return $property->isOwner($user);
    }

    public function moderate(User $user, Property $property): bool
    {
        return $user->isAdmin();
    }

    public function updateStatus(User $user, Property $property): bool
    {
        return $property->isOwner($user)
            && in_array($property->status, ['active', 'sous_reservation', 'loue']);
    }

    public function archive(User $user, Property $property): bool
    {
        return $property->isOwner($user) && $property->isActive();
    }
}
