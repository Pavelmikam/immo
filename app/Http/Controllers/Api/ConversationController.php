<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $agent  = $request->user()->agent;

        $query = Conversation::with(['client', 'agent.user', 'bien', 'dernierMessage'])
            ->withCount(['messages', 'messagesNonLus'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut));

        // Un agent voit ses conversations ; un client voit les siennes
        if ($agent) {
            $query->where(fn ($q) => $q->where('client_id', $userId)->orWhere('agent_id', $agent->id));
        } else {
            $query->where('client_id', $userId);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::with(['client', 'agent.user', 'bien'])->findOrFail($id);

        $this->autoriser($request, $conversation);

        // Marquer les messages reçus comme lus
        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json($conversation->load('messages.sender'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'bien_id'  => 'nullable|exists:biens,id',
            'message'  => 'required|string',
        ]);

        $clientId = $request->user()->id;
        $agentId  = $data['agent_id'];
        $bienId   = $data['bien_id'] ?? null;

        // Empêcher un agent de se contacter lui-même
        $agent = Agent::findOrFail($agentId);
        if ($agent->user_id === $clientId) {
            return response()->json(['message' => 'Vous ne pouvez pas vous contacter vous-même.'], 422);
        }

        // Retrouver ou créer la conversation
        $conversation = Conversation::firstOrCreate(
            ['client_id' => $clientId, 'agent_id' => $agentId, 'bien_id' => $bienId],
            ['statut' => 'ouverte']
        );

        $conversation->messages()->create([
            'sender_id' => $clientId,
            'contenu'   => $data['message'],
        ]);

        return response()->json($conversation->load(['client', 'agent.user', 'bien', 'messages.sender']), 201);
    }

    public function updateStatut(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $this->autoriser($request, $conversation);

        $data = $request->validate([
            'statut' => 'required|in:ouverte,fermee,archivee',
        ]);

        $conversation->update($data);

        return response()->json($conversation->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $this->autoriser($request, $conversation);

        $conversation->delete();

        return response()->json(['message' => 'Conversation supprimée.']);
    }

    private function autoriser(Request $request, Conversation $conversation): void
    {
        $userId  = $request->user()->id;
        $agentId = optional($request->user()->agent)->id;

        if ($conversation->client_id !== $userId && $conversation->agent_id !== $agentId) {
            abort(403, 'Action non autorisée.');
        }
    }
}
