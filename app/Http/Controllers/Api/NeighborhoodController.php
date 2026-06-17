<?php

namespace App\Http\Controllers\Api;

use App\Contracts\NeighborhoodScoreServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Neighborhood\GetNeighborhoodScoreRequest;
use App\Http\Requests\Neighborhood\SubmitNeighborhoodReportRequest;
use App\Http\Resources\ContributorProfileResource;
use App\Http\Resources\NeighborhoodReportResource;
use App\Models\NeighborhoodReport;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NeighborhoodController extends Controller
{
    public function __construct(private NeighborhoodScoreServiceInterface $service) {}

    public function submit(SubmitNeighborhoodReportRequest $request): JsonResponse
    {
        try {
            $report = $this->service->submitReport(
                $request->user(),
                $request->validated()
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new NeighborhoodReportResource($report))
                   ->response()->setStatusCode(201);
    }

    public function score(GetNeighborhoodScoreRequest $request): JsonResponse
    {
        $score = $this->service->getScoreForLocation(
            (float) $request->latitude,
            (float) $request->longitude,
            (float) ($request->radius_km ?? 2.0)
        );

        if (!$score) {
            return response()->json([
                'message' => 'Aucun score disponible pour cette zone.',
                'data'    => null,
            ]);
        }

        return response()->json(['data' => $score]);
    }

    public function scoreForProperty(Request $request, Property $property): JsonResponse
    {
        $score = $property->getNeighborhoodScore();

        return response()->json(['data' => $score]);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'city'         => ['required', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'criterion'    => ['required', 'string', 'in:eau,electricite,securite,transport,commerces,routes,sante,education'],
        ]);

        $history = $this->service->getScoreHistory(
            $request->city,
            $request->neighborhood,
            $request->criterion
        );

        return response()->json(['data' => $history]);
    }

    public function myReports(Request $request): JsonResponse
    {
        $reports = $request->user()
                           ->neighborhoodReports()
                           ->latest()
                           ->paginate(20);

        return NeighborhoodReportResource::collection($reports)->response();
    }

    public function myProfile(Request $request): JsonResponse
    {
        $user = $request->user()->load('contributorBadges');

        return ContributorProfileResource::make($user)->response();
    }

    public function reviewsForProperty(Property $property): JsonResponse
    {
        if (!$property->latitude || !$property->longitude) {
            return response()->json(['data' => []]);
        }

        $reports = NeighborhoodReport::validated()
            ->notFlagged()
            ->nearLocation((float) $property->latitude, (float) $property->longitude, 2.0)
            ->with('user')
            ->latest()
            ->paginate(10);

        return NeighborhoodReportResource::collection($reports)->response();
    }
}
