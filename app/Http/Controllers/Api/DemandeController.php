<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemandeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $demandes = Demande::with('bien', 'user')
            ->whereHas('bien', fn ($q) => $q->where('user_id', $userId))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('bien_id'), fn ($q) => $q->where('bien_id', $request->bien_id))
            ->latest()
            ->paginate(20);

        return response()->json($demandes);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $userId  = $request->user()->id;
        $demande = Demande::with('bien', 'user')->findOrFail($id);

        if (optional($demande->bien)->user_id !== $userId && $demande->user_id !== $userId) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        return response()->json($demande);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'       => 'required|string|max:100',
            'email'     => 'required|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'message'   => 'required|string',
            'bien_id'   => 'nullable|exists:biens,id',
        ]);

        $data['user_id'] = optional($request->user())->id;
        $data['statut']  = 'en_attente';

        $demande = Demande::create($data);
        $demande->load('bien');

        return response()->json($demande, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $demande = Demande::findOrFail($id);

        if (optional($demande->bien)->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $data = $request->validate([
            'statut' => 'required|in:en_attente,en_cours,traitee',
        ]);

        $demande->update($data);

        return response()->json($demande->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $demande = Demande::findOrFail($id);

        if (optional($demande->bien)->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $demande->delete();

        return response()->json(['message' => 'Demande supprimée.']);
    }
}
