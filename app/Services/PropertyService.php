<?php

namespace App\Services;

use App\Contracts\ImageServiceInterface;
use App\Contracts\PropertyServiceInterface;
use App\Models\Property;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class PropertyService implements PropertyServiceInterface
{
    public function __construct(private ImageServiceInterface $imageService) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Property::with(['images', 'owner'])->active();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }
        if (!empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }
        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }
        if (!empty($filters['min_rooms'])) {
            $query->where('rooms', '>=', $filters['min_rooms']);
        }
        if (isset($filters['featured']) && $filters['featured']) {
            $query->featured();
        }

        $query->orderByDesc('is_featured')->orderByDesc('published_at');

        return $query->paginate($perPage);
    }

    public function create(User $owner, array $data): Property
    {
        return $owner->properties()->create($data);
    }

    public function update(Property $property, array $data): Property
    {
        $property->update($data);
        return $property->fresh();
    }

    public function submit(Property $property): Property
    {
        $property->update([
            'status'           => 'pending',
            'rejection_reason' => null,
        ]);

        $property = $property->fresh();
        event(new \App\Events\PropertySubmitted($property));

        return $property;
    }

    public function approve(Property $property): Property
    {
        $property->update([
            'status'           => 'active',
            'published_at'     => $property->published_at ?? now(),
            'rejection_reason' => null,
        ]);

        $property = $property->fresh();
        event(new \App\Events\PropertyApproved($property));

        return $property;
    }

    public function reject(Property $property, string $reason): Property
    {
        $property->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $property = $property->fresh();
        event(new \App\Events\PropertyRejected($property));

        return $property;
    }

    public function archive(Property $property): Property
    {
        $property->update(['status' => 'archived']);
        return $property->fresh();
    }

    public function delete(Property $property): void
    {
        $this->imageService->deleteAllPropertyImages($property);
        $property->images()->forceDelete();
        $property->forceDelete();
    }
}
