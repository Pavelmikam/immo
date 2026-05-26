<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        $users = User::withCount(['biens', 'demandes'])
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->latest()
            ->paginate(20);

        return response()->json($users);
    }

    public function showUser(int $id): JsonResponse
    {
        $user = User::withCount(['biens', 'demandes'])
            ->with('agent')
            ->findOrFail($id);

        return response()->json($user);
    }

    public function updateRole(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'role' => 'required|in:client,agent,admin',
        ]);

        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier votre propre rôle.'], 422);
        }

        $user->update($data);

        return response()->json($user->fresh());
    }

    public function destroyUser(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }
}
