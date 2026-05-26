<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PropertyFilterServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\StoreSavedSearchRequest;
use App\Http\Requests\Search\UpdateSavedSearchRequest;
use App\Http\Resources\Property\PropertyListResource;
use App\Http\Resources\SavedSearchResource;
use App\Models\SavedSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $searches = $request->user()->savedSearches()->latest()->get();

        return SavedSearchResource::collection($searches)->response();
    }

    public function store(StoreSavedSearchRequest $request): JsonResponse
    {
        if ($request->user()->savedSearches()->count() >= 10) {
            return response()->json([
                'message' => 'Vous ne pouvez pas sauvegarder plus de 10 recherches.',
                'code'    => 'SAVED_SEARCH_LIMIT_REACHED',
            ], 422);
        }

        $search = $request->user()->savedSearches()->create($request->validated());

        return SavedSearchResource::make($search)->response()->setStatusCode(201);
    }

    public function show(Request $request, SavedSearch $savedSearch): JsonResponse
    {
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ressource introuvable.'], 404);
        }

        return SavedSearchResource::make($savedSearch)->response();
    }

    public function update(UpdateSavedSearchRequest $request, SavedSearch $savedSearch): JsonResponse
    {
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ressource introuvable.'], 404);
        }

        $savedSearch->update($request->validated());

        return SavedSearchResource::make($savedSearch->fresh())->response();
    }

    public function destroy(Request $request, SavedSearch $savedSearch): JsonResponse
    {
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ressource introuvable.'], 404);
        }

        $savedSearch->delete();

        return response()->json(null, 204);
    }

    public function toggleNotifications(Request $request, SavedSearch $savedSearch): JsonResponse
    {
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ressource introuvable.'], 404);
        }

        $savedSearch->update(['notifications_enabled' => !$savedSearch->notifications_enabled]);

        $fresh = $savedSearch->fresh();

        return response()->json([
            'message'               => $fresh->notifications_enabled
                ? 'Notifications activées.'
                : 'Notifications désactivées.',
            'notifications_enabled' => $fresh->notifications_enabled,
        ]);
    }

    public function results(
        Request $request,
        SavedSearch $savedSearch,
        PropertyFilterServiceInterface $filterService
    ): JsonResponse {
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ressource introuvable.'], 404);
        }

        $properties = $filterService->buildQuery($savedSearch->criteria)->paginate(15);

        return PropertyListResource::collection($properties)->response();
    }
}
