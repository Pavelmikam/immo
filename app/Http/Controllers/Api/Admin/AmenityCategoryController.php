<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAmenityCategoryRequest;
use App\Http\Requests\Admin\UpdateAmenityCategoryRequest;
use App\Http\Resources\AmenityCategoryResource;
use App\Models\AmenityCategory;
use App\Services\AdminLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmenityCategoryController extends Controller
{
    public function __construct(private AdminLogService $adminLogService) {}

    public function index(Request $request): JsonResponse
    {
        $query = AmenityCategory::query()
                     ->when($request->category, fn ($q) =>
                         $q->byCategory($request->category)
                     )
                     ->orderBy('category')
                     ->orderBy('sort_order')
                     ->orderBy('label');

        return AmenityCategoryResource::collection($query->get())->response();
    }

    public function store(StoreAmenityCategoryRequest $request): JsonResponse
    {
        $item = AmenityCategory::create($request->validated());

        $this->adminLogService->log(
            $request->user(), 'amenity.create', $item, [], $request->validated(), $request
        );

        return AmenityCategoryResource::make($item)->response()->setStatusCode(201);
    }

    public function update(UpdateAmenityCategoryRequest $request, AmenityCategory $amenityCategory): JsonResponse
    {
        $before = $amenityCategory->only(['label', 'is_active', 'sort_order']);
        $amenityCategory->update($request->validated());

        $this->adminLogService->log(
            $request->user(), 'amenity.update', $amenityCategory,
            $before, $request->validated(), $request
        );

        return AmenityCategoryResource::make($amenityCategory->fresh())->response();
    }

    public function destroy(Request $request, AmenityCategory $amenityCategory): JsonResponse
    {
        $amenityCategory->update(['is_active' => false]);

        $this->adminLogService->log(
            $request->user(), 'amenity.deactivate', $amenityCategory, [], [], $request
        );

        return response()->json(null, 204);
    }
}
