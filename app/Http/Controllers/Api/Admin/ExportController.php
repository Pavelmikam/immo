<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Pdf\ActivityReportPdf;
use App\Pdf\PropertyReportPdf;
use App\Services\AdminLogService;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function __construct(
        private StatisticsService $statsService,
        private AdminLogService $adminLogService
    ) {}

    public function exportProperties(Request $request): BinaryFileResponse
    {
        $request->validate([
            'format' => ['sometimes', 'in:csv,xlsx'],
            'city'   => ['sometimes', 'string', 'max:100'],
            'type'   => ['sometimes', 'string'],
            'status' => ['sometimes', 'string'],
        ]);

        $format  = $request->get('format', 'xlsx');
        $filters = $request->only(['city', 'type', 'status']);

        $this->adminLogService->log(
            $request->user(), 'export.properties', null,
            [], ['filters' => $filters], $request
        );

        return Excel::download(
            new \App\Exports\PropertiesExport($filters),
            'annonces_' . now()->format('Y-m-d') . '.' . $format
        );
    }

    public function exportUsers(Request $request): BinaryFileResponse
    {
        $request->validate([
            'format'    => ['sometimes', 'in:csv,xlsx'],
            'role'      => ['sometimes', 'in:locataire,proprietaire,admin'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $format = $request->get('format', 'xlsx');

        $this->adminLogService->log(
            $request->user(), 'export.users', null, [], [], $request
        );

        return Excel::download(
            new \App\Exports\UsersExport(
                $request->role,
                $request->has('is_active') ? $request->boolean('is_active') : null
            ),
            'utilisateurs_' . now()->format('Y-m-d') . '.' . $format
        );
    }

    public function exportRentalRequests(Request $request): BinaryFileResponse
    {
        $request->validate([
            'format'      => ['sometimes', 'in:csv,xlsx'],
            'status'      => ['sometimes', 'string'],
            'property_id' => ['sometimes', 'integer', 'exists:properties,id'],
        ]);

        $format = $request->get('format', 'xlsx');

        $this->adminLogService->log(
            $request->user(), 'export.rental_requests', null, [], [], $request
        );

        return Excel::download(
            new \App\Exports\RentalRequestsExport(
                $request->status,
                $request->property_id
            ),
            'demandes_location_' . now()->format('Y-m-d') . '.' . $format
        );
    }

    public function exportActivityReport(Request $request): Response
    {
        $request->validate([
            'period' => ['sometimes', 'in:7days,30days,90days,1year'],
        ]);

        $period = $request->get('period', '30days');
        $stats  = $this->statsService->getAdminAdvancedStats($period);

        $this->adminLogService->log(
            $request->user(), 'export.activity_report', null,
            [], ['period' => $period], $request
        );

        return (new ActivityReportPdf($stats, $period))
                ->generate()
                ->download('rapport_activite_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportPropertyReport(Request $request, Property $property): Response
    {
        if (!$property->isOwnedBy($request->user()) && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $period = $request->get('period', '30days');
        $stats  = $this->statsService->getPropertyStats($property, $period);

        return (new PropertyReportPdf($property, $stats))
                ->generate()
                ->download("rapport_{$property->id}_" . now()->format('Y-m-d') . '.pdf');
    }
}
