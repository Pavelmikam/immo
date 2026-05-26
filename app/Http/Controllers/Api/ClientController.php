<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Models\Favori;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'favoris'              => Favori::where('user_id', $userId)->count(),
            'demandes'             => [
                'total'      => Demande::where('user_id', $userId)->count(),
                'en_attente' => Demande::where('user_id', $userId)->where('statut', 'en_attente')->count(),
                'en_cours'   => Demande::where('user_id', $userId)->where('statut', 'en_cours')->count(),
                'traitees'   => Demande::where('user_id', $userId)->where('statut', 'traitee')->count(),
            ],
            'conversations'        => [
                'total'    => Conversation::where('client_id', $userId)->count(),
                'ouvertes' => Conversation::where('client_id', $userId)->where('statut', 'ouverte')->count(),
            ],
            'messages_non_lus'     => \App\Models\Message::whereHas(
                'conversation',
                fn ($q) => $q->where('client_id', $userId)
            )->where('lu', false)->where('sender_id', '!=', $userId)->count(),
        ]);
    }

    public function biensFavoris(Request $request): JsonResponse
    {
        $biens = Favori::with(['bien.typeBien', 'bien.ville', 'bien.agent.user'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12)
            ->through(fn ($f) => $f->bien);

        return response()->json($biens);
    }

    public function demandesRecentes(Request $request): JsonResponse
    {
        $demandes = Demande::with('bien.typeBien')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json($demandes);
    }
}
