<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        $this->autoriser($request, $conversation);

        $messages = $conversation->messages()
            ->with('sender')
            ->oldest()
            ->paginate(50);

        // Marquer les messages reçus comme lus
        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json($messages);
    }

    public function store(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        $this->autoriser($request, $conversation);

        if ($conversation->statut !== 'ouverte') {
            return response()->json(['message' => 'Cette conversation est fermée.'], 422);
        }

        $data = $request->validate([
            'contenu' => 'required|string',
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'contenu'   => $data['contenu'],
        ]);

        return response()->json($message->load('sender'), 201);
    }

    public function destroy(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        $this->autoriser($request, $conversation);

        $message = $conversation->messages()->findOrFail($messageId);

        if ($message->sender_id !== $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez supprimer que vos propres messages.'], 403);
        }

        $message->delete();

        return response()->json(['message' => 'Message supprimé.']);
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
