<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminLogResource;
use App\Models\AdminLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AdminLog::with('admin')
                         ->when($request->admin_id, fn ($q) =>
                             $q->byAdmin($request->admin_id)
                         )
                         ->when($request->action, fn ($q) =>
                             $q->byAction($request->action)
                         )
                         ->when($request->date_from, fn ($q) =>
                             $q->whereDate('created_at', '>=', $request->date_from)
                         )
                         ->when($request->date_to, fn ($q) =>
                             $q->whereDate('created_at', '<=', $request->date_to)
                         )
                         ->latest()
                         ->paginate(50);

        return AdminLogResource::collection($query)->response();
    }
}
