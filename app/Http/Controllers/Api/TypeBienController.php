<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TypeBien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypeBienController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(TypeBien::withCount('biens')->get());
    }

    public function show(int $id): JsonResponse
    {
        $type = TypeBien::withCount('biens')->findOrFail($id);

        return response()->json($type);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:100|unique:type_biens,nom',
            'description' => 'nullable|string',
        ]);

        $type = TypeBien::create($data);

        return response()->json($type, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = TypeBien::findOrFail($id);

        $data = $request->validate([
            'nom'         => 'sometimes|string|max:100|unique:type_biens,nom,' . $id,
            'description' => 'nullable|string',
        ]);

        $type->update($data);

        return response()->json($type->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $type = TypeBien::findOrFail($id);

        if ($type->biens()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un type ayant des biens associés.',
            ], 422);
        }

        $type->delete();

        return response()->json(['message' => 'Type de bien supprimé.']);
    }
}
