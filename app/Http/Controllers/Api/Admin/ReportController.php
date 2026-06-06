<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HandleReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Message;
use App\Models\Property;
use App\Models\Report;
use App\Services\AdminLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private AdminLogService $adminLogService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Report::with(['reporter', 'reportable', 'handledByAdmin'])
                       ->when($request->status, fn ($q) =>
                           $q->byStatus($request->status)
                       )
                       ->when($request->type === 'property', fn ($q) =>
                           $q->where('reportable_type', Property::class)
                       )
                       ->when($request->type === 'message', fn ($q) =>
                           $q->where('reportable_type', Message::class)
                       )
                       ->latest()
                       ->paginate(20);

        return ReportResource::collection($query)->response();
    }

    public function handle(HandleReportRequest $request, Report $report): JsonResponse
    {
        if ($report->isResolved()) {
            return response()->json([
                'message' => 'Ce signalement est déjà traité.',
            ], 422);
        }

        $statusMap = [
            'resolve'     => 'resolu',
            'reject'      => 'rejete',
            'in_progress' => 'en_cours',
        ];

        $before = $report->only(['status', 'admin_note']);
        $report->update([
            'status'     => $statusMap[$request->action],
            'admin_note' => $request->admin_note,
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);

        $this->adminLogService->log(
            $request->user(), 'report.' . $request->action, $report,
            $before,
            ['status' => $statusMap[$request->action]],
            $request
        );

        return response()->json([
            'message' => 'Signalement traité.',
            'report'  => ReportResource::make(
                $report->fresh()->load(['reporter', 'reportable'])
            ),
        ]);
    }
}
