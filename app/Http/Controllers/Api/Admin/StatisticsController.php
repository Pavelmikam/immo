<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Property\PropertyListResource;
use App\Models\Property;
use App\Models\PropertyView;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function __construct(private StatisticsService $statsService) {}

    public function advanced(Request $request): JsonResponse
    {
        $period = $request->get('period', '30days');
        $city   = $request->get('city');

        return response()->json([
            'data' => $this->statsService->getAdminAdvancedStats($period, $city),
        ]);
    }

    public function viewsTimeline(Request $request): JsonResponse
    {
        $request->validate([
            'period'      => ['sometimes', 'in:7days,30days,90days'],
            'property_id' => ['sometimes', 'integer', 'exists:properties,id'],
        ]);

        $start = now()->subDays(match ($request->period ?? '30days') {
            '7days'  => 7,
            '90days' => 90,
            default  => 30,
        });

        $data = PropertyView::where('viewed_at', '>=', $start)
            ->when($request->property_id,
                fn ($q) => $q->where('property_id', $request->property_id)
            )
            ->selectRaw('DATE(viewed_at) as date,
                         COUNT(*) as total_views,
                         COUNT(DISTINCT COALESCE(user_id, session_id)) as unique_views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function topProperties(Request $request): JsonResponse
    {
        $metric = $request->get('metric', 'views_count');

        if (!in_array($metric, ['views_count', 'favorites_count', 'requests_count'])) {
            return response()->json(['message' => 'Métrique invalide.'], 422);
        }

        $properties = Property::with(['owner', 'primaryImage'])
                               ->orderByDesc($metric)
                               ->limit(20)
                               ->get();

        return PropertyListResource::collection($properties)->response();
    }
}
