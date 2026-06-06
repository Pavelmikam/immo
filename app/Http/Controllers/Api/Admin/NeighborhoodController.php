<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\NeighborhoodScoreServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\NeighborhoodReportResource;
use App\Models\NeighborhoodReport;
use App\Services\AdminLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NeighborhoodController extends Controller
{
    public function __construct(
        private NeighborhoodScoreServiceInterface $service,
        private AdminLogService $adminLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = NeighborhoodReport::with('user')
                     ->when($request->has('is_flagged'),
                         fn ($q) => $q->where('is_flagged', $request->boolean('is_flagged'))
                     )
                     ->when($request->criterion, fn ($q) => $q->byCriterion($request->criterion))
                     ->when($request->city, fn ($q) => $q->byCity($request->city))
                     ->latest()
                     ->paginate(30);

        return NeighborhoodReportResource::collection($query)->response();
    }

    public function flag(Request $request, NeighborhoodReport $neighborhoodReport): JsonResponse
    {
        $neighborhoodReport->update([
            'is_flagged'   => true,
            'is_validated' => false,
        ]);

        $this->adminLogService->log(
            $request->user(), 'neighborhood_report.flag',
            $neighborhoodReport, [], [], $request
        );

        return response()->json(['message' => 'Rapport signalé comme suspect.']);
    }

    public function validate(Request $request, NeighborhoodReport $neighborhoodReport): JsonResponse
    {
        $neighborhoodReport->update([
            'is_flagged'   => false,
            'is_validated' => true,
        ]);

        $this->adminLogService->log(
            $request->user(), 'neighborhood_report.validate',
            $neighborhoodReport, [], [], $request
        );

        return response()->json(['message' => 'Rapport revalidé.']);
    }

    public function recompute(Request $request): JsonResponse
    {
        $request->validate([
            'city'         => ['required', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
        ]);

        $scores = $this->service->computeScore(
            $request->city,
            $request->neighborhood
        );

        $this->adminLogService->log(
            $request->user(), 'neighborhood_score.recompute',
            null, [], [
                'city'           => $request->city,
                'scores_updated' => $scores->count(),
            ],
            $request
        );

        return response()->json([
            'message'        => 'Scores recalculés.',
            'scores_updated' => $scores->count(),
        ]);
    }
}
