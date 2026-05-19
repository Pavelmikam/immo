<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BienController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Bien::with('typeBien', 'proprietaire', 'agent')
            ->where('disponible', true);

        if ($request->filled('ville')) {
            $query->where('ville', 'like', '%' . $request->ville . '%');
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('type_bien_id')) {
            $query->where('type_bien_id', $request->type_bien_id);
        }

        if ($request->filled('prix_min')) {
            $query->where('prix', '>=', $request->prix_min);
        }

        if ($request->filled('prix_max')) {
            $query->where('prix', '<=', $request->prix_max);
        }

        $biens = $query->latest()->paginate(12);

        return response()->json($biens);
    }

    public function show(int $id): JsonResponse
    {
        $bien = Bien::with('typeBien', 'proprietaire', 'agent')->findOrFail($id);

        return response()->json($bien);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'titre'          => 'required|string|max:255',
            'description'    => 'required|string',
            'prix'           => 'required|numeric|min:0',
            'surface'        => 'required|numeric|min:0',
            'nb_pieces'      => 'required|integer|min:1',
            'nb_chambres'    => 'nullable|integer|min:0',
            'nb_salles_bain' => 'nullable|integer|min:0',
            'adresse'        => 'required|string|max:255',
            'ville'          => 'required|string|max:100',
            'code_postal'    => 'nullable|string|max:10',
            'statut'         => 'required|in:vente,location',
            'type_bien_id'   => 'required|exists:type_biens,id',
        ]);

        $data['user_id']    = $request->user()->id;
        $data['agent_id']   = optional($request->user()->agent)->id;
        $data['disponible'] = true;

        $bien = Bien::create($data);
        $bien->load('typeBien');

        return response()->json($bien, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bien = Bien::where('user_id', $request->user()->id)->findOrFail($id);

        $data = $request->validate([
            'titre'          => 'sometimes|string|max:255',
            'description'    => 'sometimes|string',
            'prix'           => 'sometimes|numeric|min:0',
            'surface'        => 'sometimes|numeric|min:0',
            'nb_pieces'      => 'sometimes|integer|min:1',
            'nb_chambres'    => 'nullable|integer|min:0',
            'nb_salles_bain' => 'nullable|integer|min:0',
            'adresse'        => 'sometimes|string|max:255',
            'ville'          => 'sometimes|string|max:100',
            'code_postal'    => 'nullable|string|max:10',
            'statut'         => 'sometimes|in:vente,location',
            'disponible'     => 'sometimes|boolean',
            'type_bien_id'   => 'sometimes|exists:type_biens,id',
        ]);

        $bien->update($data);

        return response()->json($bien->fresh('typeBien'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $bien = Bien::where('user_id', $request->user()->id)->findOrFail($id);
        $bien->delete();

        return response()->json(['message' => 'Bien supprimé.']);
    }

    public function mesBiens(Request $request): JsonResponse
    {
        $biens = Bien::with('typeBien')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return response()->json($biens);
    }
}
