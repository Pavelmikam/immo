<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(): JsonResponse
    {
        $agents = Agent::with('user')
            ->withCount('biens')
            ->paginate(15);

        return response()->json($agents);
    }

    public function show(int $id): JsonResponse
    {
        $agent = Agent::with(['user', 'biens.typeBien'])
            ->withCount('biens')
            ->findOrFail($id);

        return response()->json($agent);
    }

    public function monProfil(Request $request): JsonResponse
    {
        $agent = Agent::with('user')
            ->withCount('biens')
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json($agent);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->agent) {
            return response()->json(['message' => 'Vous avez déjà un profil agent.'], 422);
        }

        $data = $request->validate([
            'telephone'  => 'nullable|string|max:20',
            'specialite' => 'nullable|string|max:255',
            'biographie' => 'nullable|string',
        ]);

        $data['user_id'] = $request->user()->id;

        $agent = Agent::create($data);

        return response()->json($agent->load('user'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $agent = Agent::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'telephone'  => 'sometimes|nullable|string|max:20',
            'specialite' => 'nullable|string|max:255',
            'biographie' => 'nullable|string',
        ]);

        $agent->update($data);

        return response()->json($agent->fresh('user'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $agent = Agent::where('user_id', $request->user()->id)->findOrFail($id);
        $agent->delete();

        return response()->json(['message' => 'Agent supprimé.']);
    }
}
