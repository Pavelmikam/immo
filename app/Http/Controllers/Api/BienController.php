<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BienController extends Controller
{
    // =========================================================================
    // RECHERCHE CLIENT — GET /api/biens
    // =========================================================================
    // Filtres disponibles :
    //   ?type_bien_id=   → type exact (Appartement, Maison, Chambre, Hôtel)
    //   ?statut=         → vente | location
    //   ?quartier=       → nom du quartier (recherche partielle)
    //   ?ville=          → nom de la ville (recherche partielle)
    //   ?ville_id=       → ID ville (recherche exacte)
    //   ?region_id=      → ID région (via la relation ville)
    //   ?prix_min=       → budget minimum
    //   ?prix_max=       → budget maximum
    //   ?surface_min=    → surface minimale (m²)
    //   ?nb_pieces_min=  → nombre de pièces minimum
    // =========================================================================

    public function index(Request $request): JsonResponse
    {
        $query = Bien::with('typeBien', 'proprietaire', 'agent', 'ville.region')
            ->where('disponible', true);

        // Filtre par type de bien (Appartement, Maison, Chambre, Hôtel…)
        if ($request->filled('type_bien_id')) {
            $query->where('type_bien_id', $request->type_bien_id);
        }

        // Filtre par statut (vente / location)
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre par quartier (recherche partielle)
        if ($request->filled('quartier')) {
            $query->where('quartier', 'like', '%' . $request->quartier . '%');
        }

        // Filtre par ville (texte libre)
        if ($request->filled('ville')) {
            $query->where('ville', 'like', '%' . $request->ville . '%');
        }

        // Filtre par ville_id (référentiel)
        if ($request->filled('ville_id')) {
            $query->where('ville_id', $request->ville_id);
        }

        // Filtre par région (via la relation ville)
        if ($request->filled('region_id')) {
            $query->whereHas('ville', fn ($q) => $q->where('region_id', $request->region_id));
        }

        // Budget
        if ($request->filled('prix_min')) {
            $query->where('prix', '>=', $request->prix_min);
        }
        if ($request->filled('prix_max')) {
            $query->where('prix', '<=', $request->prix_max);
        }

        // Surface minimale
        if ($request->filled('surface_min')) {
            $query->where('surface', '>=', $request->surface_min);
        }

        // Nombre de pièces minimum
        if ($request->filled('nb_pieces_min')) {
            $query->where('nb_pieces', '>=', $request->nb_pieces_min);
        }

        $biens = $query->latest()->paginate(12);

        return response()->json($biens);
    }

    // =========================================================================
    // DÉTAIL D'UN BIEN — GET /api/biens/{id}
    // =========================================================================

    public function show(int $id): JsonResponse
    {
        $bien = Bien::with('typeBien', 'proprietaire', 'agent', 'ville.region')
            ->findOrFail($id);

        return response()->json($bien);
    }

    // =========================================================================
    // CRUD AGENT
    // =========================================================================

    // POST /api/biens — Publier un nouveau bien
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
            'quartier'       => 'nullable|string|max:100',
            'ville'          => 'required|string|max:100',
            'ville_id'       => 'nullable|exists:villes,id',
            'code_postal'    => 'nullable|string|max:10',
            'statut'         => 'required|in:vente,location',
            'type_bien_id'   => 'required|exists:type_biens,id',
        ]);

        $data['user_id']    = $request->user()->id;
        $data['agent_id']   = optional($request->user()->agent)->id;
        $data['disponible'] = true;

        $bien = Bien::create($data);
        $bien->load('typeBien', 'ville.region');

        return response()->json($bien, 201);
    }

    // PUT /api/biens/{id} — Modifier un bien (agent propriétaire uniquement)
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
            'quartier'       => 'nullable|string|max:100',
            'ville'          => 'sometimes|string|max:100',
            'ville_id'       => 'nullable|exists:villes,id',
            'code_postal'    => 'nullable|string|max:10',
            'statut'         => 'sometimes|in:vente,location',
            'disponible'     => 'sometimes|boolean',
            'type_bien_id'   => 'sometimes|exists:type_biens,id',
        ]);

        $bien->update($data);

        return response()->json($bien->fresh(['typeBien', 'ville.region']));
    }

    // DELETE /api/biens/{id} — Supprimer un bien (agent propriétaire uniquement)
    public function destroy(Request $request, int $id): JsonResponse
    {
        $bien = Bien::where('user_id', $request->user()->id)->findOrFail($id);
        $bien->delete();

        return response()->json(['message' => 'Bien supprimé.']);
    }

    // GET /api/mes-biens — Tous les biens de l'agent connecté
    public function mesBiens(Request $request): JsonResponse
    {
        $biens = Bien::with('typeBien', 'ville')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return response()->json($biens);
    }

    // GET /api/biens/{id}/demandes — Demandes reçues sur un bien (agent propriétaire)
    public function demandes(Request $request, int $id): JsonResponse
    {
        $bien = Bien::where('user_id', $request->user()->id)->findOrFail($id);

        $demandes = $bien->demandes()
            ->with('user')
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->latest()
            ->paginate(20);

        return response()->json($demandes);
    }
}
