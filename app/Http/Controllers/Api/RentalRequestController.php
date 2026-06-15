<?php

namespace App\Http\Controllers\Api;

use App\Contracts\RentalRequestServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\RentalRequest\DecideRentalRequestRequest;
use App\Http\Requests\RentalRequest\ScheduleVisitRequest;
use App\Http\Requests\RentalRequest\StoreRentalRequestRequest;
use App\Http\Resources\RentalRequestListResource;
use App\Http\Resources\RentalRequestResource;
use App\Models\Property;
use App\Models\RentalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalRequestController extends Controller
{
    public function __construct(private RentalRequestServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isLocataire()) {
            $query = RentalRequest::with(['property.primaryImage'])
                                  ->forTenant($user->id);
        } elseif ($user->isProprietaire()) {
            $propertyIds = Property::byOwner($user->id)->pluck('id');
            $query = RentalRequest::with(['property.primaryImage', 'tenant'])
                                  ->whereIn('property_id', $propertyIds);
        } else {
            $query = RentalRequest::with(['property.primaryImage', 'tenant']);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', (int) $request->property_id);
        }

        $requests = $query->latest()->paginate(15);

        return RentalRequestListResource::collection($requests)->response()->setStatusCode(200);
    }

    public function store(StoreRentalRequestRequest $request, Property $property): JsonResponse
    {
        $this->authorize('create', RentalRequest::class);

        try {
            $rentalRequest = $this->service->createRequest(
                $request->user(),
                $property,
                $request->validated()
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'DOMAIN_ERROR'], 422);
        }

        return (new RentalRequestResource($rentalRequest))->response()->setStatusCode(201);
    }

    public function show(Request $request, RentalRequest $rentalRequest): JsonResponse
    {
        $this->authorize('view', $rentalRequest);

        $rentalRequest->load(['property.primaryImage', 'tenant', 'documents']);

        return RentalRequestResource::make($rentalRequest)->response();
    }

    public function decide(DecideRentalRequestRequest $request, RentalRequest $rentalRequest): JsonResponse
    {
        if ($request->action === 'accept') {
            $this->authorize('accept', $rentalRequest);
            try {
                $result = $this->service->acceptRequest(
                    $rentalRequest,
                    $request->user(),
                    $request->validated('owner_response')
                );
            } catch (\DomainException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            $message = 'Demande acceptée. Les autres candidatures ont été refusées.';
        } else {
            $this->authorize('refuse', $rentalRequest);
            try {
                $result = $this->service->refuseRequest(
                    $rentalRequest,
                    $request->user(),
                    $request->validated('owner_response')
                );
            } catch (\DomainException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            $message = 'Demande refusée.';
        }

        return response()->json([
            'message' => $message,
            'data'    => new RentalRequestResource($result),
        ]);
    }

    public function cancel(Request $request, RentalRequest $rentalRequest): JsonResponse
    {
        $this->authorize('cancel', $rentalRequest);

        try {
            $this->service->cancelRequest($rentalRequest, $request->user());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Demande annulée.']);
    }

    public function scheduleVisit(ScheduleVisitRequest $request, RentalRequest $rentalRequest): JsonResponse
    {
        if (!$rentalRequest->property->isOwnedBy($request->user())) {
            abort(403);
        }

        if (!in_array($rentalRequest->status, ['en_attente', 'acceptee'])) {
            return response()->json(['message' => 'Impossible de planifier une visite sur cette demande.'], 422);
        }

        $rentalRequest->update([
            'visit_scheduled_at' => $request->validated('visit_scheduled_at'),
            'visit_confirmed'    => false,
        ]);

        $rentalRequest->refresh()->load(['property', 'tenant']);
        $rentalRequest->tenant->notify(
            new \App\Notifications\VisitScheduledNotification($rentalRequest)
        );

        return response()->json(['message' => 'Visite planifiée.']);
    }

    public function confirmVisit(Request $request, RentalRequest $rentalRequest): JsonResponse
    {
        if ($rentalRequest->tenant_id !== $request->user()->id) {
            abort(403);
        }

        if (!$rentalRequest->visit_scheduled_at) {
            return response()->json(['message' => 'Aucune visite planifiée pour cette demande.'], 422);
        }

        $rentalRequest->update(['visit_confirmed' => true]);

        return response()->json(['message' => 'Visite confirmée.']);
    }
}
