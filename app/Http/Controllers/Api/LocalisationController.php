<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Ville;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalisationController extends Controller
{
    // -------------------------------------------------------------------------
    // RÉGIONS
    // -------------------------------------------------------------------------

    public function regions(): JsonResponse
    {
        $regions = Region::withCount('villes')->orderBy('nom')->get();

        return response()->json($regions);
    }

    public function showRegion(int $id): JsonResponse
    {
        $region = Region::withCount('villes')->with('villes')->findOrFail($id);

        return response()->json($region);
    }

    public function storeRegion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'  => 'required|string|max:100|unique:regions,nom',
            'code' => 'nullable|string|max:10|unique:regions,code',
        ]);

        $region = Region::create($data);

        return response()->json($region, 201);
    }

    public function updateRegion(Request $request, int $id): JsonResponse
    {
        $region = Region::findOrFail($id);

        $data = $request->validate([
            'nom'  => 'sometimes|string|max:100|unique:regions,nom,' . $id,
            'code' => 'nullable|string|max:10|unique:regions,code,' . $id,
        ]);

        $region->update($data);

        return response()->json($region->fresh());
    }

    public function destroyRegion(int $id): JsonResponse
    {
        $region = Region::findOrFail($id);

        if ($region->villes()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une région ayant des villes associées.',
            ], 422);
        }

        $region->delete();

        return response()->json(['message' => 'Région supprimée.']);
    }

    // -------------------------------------------------------------------------
    // VILLES
    // -------------------------------------------------------------------------

    public function villes(Request $request): JsonResponse
    {
        $villes = Ville::with('region')
            ->withCount('biens')
            ->when($request->filled('region_id'), fn ($q) => $q->where('region_id', $request->region_id))
            ->when($request->filled('q'), fn ($q) => $q->where('nom', 'like', '%' . $request->q . '%'))
            ->orderBy('nom')
            ->get();

        return response()->json($villes);
    }

    public function showVille(int $id): JsonResponse
    {
        $ville = Ville::with('region')->withCount('biens')->findOrFail($id);

        return response()->json($ville);
    }

    public function storeVille(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:100',
            'code_postal' => 'nullable|string|max:10',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'region_id'   => 'required|exists:regions,id',
        ]);

        $ville = Ville::create($data);

        return response()->json($ville->load('region'), 201);
    }

    public function updateVille(Request $request, int $id): JsonResponse
    {
        $ville = Ville::findOrFail($id);

        $data = $request->validate([
            'nom'         => 'sometimes|string|max:100',
            'code_postal' => 'nullable|string|max:10',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'region_id'   => 'sometimes|exists:regions,id',
        ]);

        $ville->update($data);

        return response()->json($ville->fresh('region'));
    }

    public function destroyVille(int $id): JsonResponse
    {
        $ville = Ville::findOrFail($id);

        if ($ville->biens()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une ville ayant des biens associés.',
            ], 422);
        }

        $ville->delete();

        return response()->json(['message' => 'Ville supprimée.']);
    }
}
