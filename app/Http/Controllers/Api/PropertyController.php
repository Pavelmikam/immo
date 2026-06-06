<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PropertyFilterServiceInterface;
use App\Contracts\PropertyServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Http\Requests\Search\PropertySearchRequest;
use App\Http\Resources\Property\PropertyListResource;
use App\Http\Resources\Property\PropertyMapResource;
use App\Http\Resources\Property\PropertyResource;
use App\Models\AmenityCategory;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PropertyController extends Controller
{
    public function __construct(
        private PropertyServiceInterface $propertyService,
        private PropertyFilterServiceInterface $filterService,
    ) {}

    public function index(PropertySearchRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = $filters['per_page'] ?? 15;

        if ($request->user()) {
            $request->user()->load('favorites');
        }

        $properties = $this->filterService->buildQuery($filters)->paginate($perPage);

        return PropertyListResource::collection($properties)->response()->setStatusCode(200);
    }

    public function map(PropertySearchRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $properties = $this->filterService
                           ->buildQuery($filters)
                           ->with('primaryImage')
                           ->whereNotNull('latitude')
                           ->whereNotNull('longitude')
                           ->limit(200)
                           ->get();

        return PropertyMapResource::collection($properties)->response()->setStatusCode(200);
    }

    public function show(\Illuminate\Http\Request $request, Property $property): PropertyResource
    {
        $user = auth('sanctum')->user();

        if (!Gate::forUser($user)->allows('view', $property)) {
            abort(403);
        }

        $property->load(['images', 'owner']);

        if (!$user || !$property->isOwnedBy($user)) {
            $sessionId = md5(
                ($request->ip() ?? 'unknown') . '|' .
                ($request->userAgent() ?? 'unknown')
            );

            $property->recordView(
                userId   : $user?->id,
                sessionId: $sessionId,
                ip       : $request->ip(),
                userAgent: $request->userAgent(),
                referrer : $request->header('Referer')
            );
        }

        return new PropertyResource($property);
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $this->authorize('create', Property::class);

        $property = $this->propertyService->create($request->user(), $request->validated());

        return response()->json(new PropertyResource($property->load('images')), 201);
    }

    public function update(UpdatePropertyRequest $request, Property $property): PropertyResource
    {
        $this->authorize('update', $property);

        $property = $this->propertyService->update($property, $request->validated());

        return new PropertyResource($property->load('images'));
    }

    public function submit(Request $request, Property $property): PropertyResource
    {
        $this->authorize('submit', $property);

        $property = $this->propertyService->submit($property);

        return new PropertyResource($property->load('images'));
    }

    public function archive(Request $request, Property $property): PropertyResource
    {
        $this->authorize('archive', $property);

        $property = $this->propertyService->archive($property);

        return new PropertyResource($property->load('images'));
    }

    public function destroy(Property $property): JsonResponse
    {
        $this->authorize('delete', $property);

        $this->propertyService->delete($property);

        return response()->json(null, 204);
    }

    public function amenities(): JsonResponse
    {
        return response()->json([
            'property_types' => AmenityCategory::propertyTypes()->orderBy('sort_order')->get(['value', 'label']),
            'amenities'      => AmenityCategory::amenities()->orderBy('sort_order')->get(['value', 'label']),
            'charges'        => AmenityCategory::charges()->orderBy('sort_order')->get(['value', 'label']),
        ]);
    }
}
