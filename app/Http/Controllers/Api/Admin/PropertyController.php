<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\PropertyServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ModeratePropertyRequest;
use App\Http\Resources\Property\PropertyListResource;
use App\Http\Resources\Property\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function __construct(private PropertyServiceInterface $propertyService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Property::with(['images', 'owner'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn ($inner) =>
                    $inner->where('title', 'LIKE', $term)
                          ->orWhere('city', 'LIKE', $term)
                          ->orWhereHas('owner', fn ($ow) =>
                              $ow->where('name', 'LIKE', $term)
                                 ->orWhere('email', 'LIKE', $term)
                          )
                );
            })
            ->orderByRaw("FIELD(status, 'pending', 'active', 'rejected', 'draft', 'archived', 'sous_reservation')")
            ->orderByDesc('created_at');

        $paginated = $query->paginate((int) $request->get('per_page', 20));

        return PropertyListResource::collection($paginated);
    }

    public function moderate(ModeratePropertyRequest $request, Property $property): PropertyResource
    {
        $this->authorize('moderate', $property);

        if (! $property->isPending()) {
            abort(422, 'Seules les annonces en attente de validation peuvent être modérées.');
        }

        $property = match ($request->input('action')) {
            'approve' => $this->propertyService->approve($property),
            'reject'  => $this->propertyService->reject($property, $request->input('rejection_reason')),
        };

        return new PropertyResource($property->load(['images', 'owner']));
    }
}
