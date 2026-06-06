<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Property\PropertyListResource;
use App\Models\Property;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function __construct(private StatisticsService $statsService) {}

    private function validatePeriod(string $period): void
    {
        if (!in_array($period, ['7days', '30days', '90days', '1year'])) {
            abort(422, 'Période invalide. Valeurs : 7days, 30days, 90days, 1year');
        }
    }

    public function propertyStats(Request $request, Property $property): JsonResponse
    {
        if (!$property->isOwnedBy($request->user()) && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $period = $request->get('period', '30days');
        $this->validatePeriod($period);

        return response()->json([
            'data' => $this->statsService->getPropertyStats($property, $period),
        ]);
    }

    public function ownerDashboard(Request $request): JsonResponse
    {
        if (!$request->user()->isProprietaire() && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Réservé aux propriétaires.'], 403);
        }

        $period = $request->get('period', '30days');
        $this->validatePeriod($period);

        return response()->json([
            'data' => $this->statsService->getOwnerDashboard($request->user(), $period),
        ]);
    }

    public function tenantDashboard(Request $request): JsonResponse
    {
        if (!$request->user()->isLocataire()) {
            return response()->json(['message' => 'Réservé aux locataires.'], 403);
        }

        $period = $request->get('period', '30days');
        $this->validatePeriod($period);

        return response()->json([
            'data' => $this->statsService->getTenantDashboard($request->user(), $period),
        ]);
    }

    public function popularProperties(): JsonResponse
    {
        $popular = Property::public()
                           ->with('primaryImage')
                           ->orderByDesc('views_count')
                           ->limit(10)
                           ->get();

        return PropertyListResource::collection($popular)->response();
    }
}
