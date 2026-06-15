<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserListRequest;
use App\Http\Requests\Admin\SuspendUserRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use App\Services\AdminLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private AdminLogService $adminLogService) {}

    public function index(AdminUserListRequest $request): JsonResponse
    {
        $query = User::withTrashed()
                     ->when($request->role, fn ($q) => $q->where('role', $request->role))
                     ->when($request->has('is_active'), fn ($q) =>
                         $q->where('is_active', $request->boolean('is_active'))
                     )
                     ->when($request->boolean('deleted'), fn ($q) => $q->onlyTrashed())
                     ->when($request->search, fn ($q) =>
                         $q->where(fn ($inner) =>
                             $inner->where('name', 'LIKE', "%{$request->search}%")
                                   ->orWhere('email', 'LIKE', "%{$request->search}%")
                         )
                     );

        match ($request->sort ?? 'newest') {
            'oldest'    => $query->oldest(),
            'name_asc'  => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            default     => $query->latest(),
        };

        return AdminUserResource::collection(
            $query->paginate($request->per_page ?? 20)
        )->response();
    }

    public function show(Request $request, int $userId): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->loadCount(['properties', 'rentalRequests', 'reportsSubmitted', 'conversations']);

        return AdminUserResource::make($user)->response();
    }

    public function suspend(SuspendUserRequest $request, User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Impossible de suspendre un administrateur.',
            ], 422);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas suspendre votre propre compte.',
            ], 422);
        }

        $before = ['is_active' => $user->is_active];
        $user->update(['is_active' => false]);

        $this->adminLogService->log(
            $request->user(), 'user.suspend', $user,
            $before, ['is_active' => false, 'reason' => $request->reason],
            $request
        );

        return response()->json([
            'message' => 'Compte suspendu avec succès.',
            'user'    => AdminUserResource::make($user->fresh()),
        ]);
    }

    public function activate(Request $request, User $user): JsonResponse
    {
        $before = ['is_active' => $user->is_active];
        $user->update(['is_active' => true]);

        $this->adminLogService->log(
            $request->user(), 'user.activate', $user,
            $before, ['is_active' => true], $request
        );

        return response()->json([
            'message' => 'Compte réactivé avec succès.',
            'user'    => AdminUserResource::make($user->fresh()),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Impossible de supprimer un administrateur.',
            ], 422);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 422);
        }

        $this->adminLogService->log(
            $request->user(), 'user.delete', $user,
            ['name' => $user->name, 'email' => $user->email], [], $request
        );

        $user->delete();

        return response()->json(null, 204);
    }

    public function restore(Request $request, int $userId): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($userId);

        if (!$user->trashed()) {
            return response()->json(['message' => "Ce compte n'est pas supprimé."], 422);
        }

        $user->restore();

        $this->adminLogService->log($request->user(), 'user.restore', $user, [], [], $request);

        return response()->json([
            'message' => 'Compte restauré avec succès.',
            'user'    => AdminUserResource::make($user->fresh()),
        ]);
    }
}
