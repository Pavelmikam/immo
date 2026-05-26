<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favoris = Favori::with(['bien.typeBien', 'bien.ville'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return response()->json($favoris);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bien_id' => 'required|exists:biens,id',
        ]);

        $favori = Favori::firstOrCreate([
            'user_id' => $request->user()->id,
            'bien_id' => $data['bien_id'],
        ]);

        return response()->json($favori->load('bien.typeBien'), 201);
    }

    public function destroy(Request $request, int $bienId): JsonResponse
    {
        $deleted = Favori::where('user_id', $request->user()->id)
            ->where('bien_id', $bienId)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Ce bien n\'est pas dans vos favoris.'], 404);
        }

        return response()->json(['message' => 'Bien retiré des favoris.']);
    }
}
