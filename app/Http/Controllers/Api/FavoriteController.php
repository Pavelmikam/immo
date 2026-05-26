<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Property\PropertyListResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->user()->load('favorites');

        $favorites = $request->user()
                             ->favorites()
                             ->with(['images', 'owner'])
                             ->public()
                             ->latest('favorites.created_at')
                             ->paginate(15);

        return PropertyListResource::collection($favorites)->response()->setStatusCode(200);
    }

    public function toggle(Request $request, Property $property): JsonResponse
    {
        $user = $request->user();

        if (!$user->isLocataire() && !$user->isProprietaire()) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        if ($user->favorites()->where('property_id', $property->id)->exists()) {
            $user->favorites()->detach($property->id);

            Property::withoutTimestamps(fn () => $property->decrement('favorites_count'));

            return response()->json([
                'message'         => 'Retiré des favoris.',
                'is_favorited'    => false,
                'favorites_count' => max(0, $property->fresh()->favorites_count),
            ]);
        }

        $user->favorites()->attach($property->id);

        Property::withoutTimestamps(fn () => $property->increment('favorites_count'));

        return response()->json([
            'message'         => 'Ajouté aux favoris.',
            'is_favorited'    => true,
            'favorites_count' => $property->fresh()->favorites_count,
        ]);
    }

    public function check(Request $request, Property $property): JsonResponse
    {
        return response()->json([
            'is_favorited'    => $request->user()->hasFavorited($property->id),
            'favorites_count' => $property->favorites_count,
        ]);
    }
}
