<?php

namespace App\Http\Controllers\Api;

use App\Contracts\MessagingServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Messaging\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private MessagingServiceInterface $service) {}

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
                                 ->with(['sender', 'attachments'])
                                 ->orderBy('created_at')
                                 ->paginate(
                                     $request->integer('per_page', 30)
                                 );

        $this->service->markAsRead($conversation, $request->user());

        return MessageResource::collection($messages)->response();
    }

    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $attachments = $request->file('attachments') ?? [];

        try {
            $message = $this->service->sendMessage(
                $conversation,
                $request->user(),
                $request->validated('body'),
                $attachments
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new MessageResource($message->load(['sender', 'attachments'])))
                   ->response()->setStatusCode(201);
    }

    public function since(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $sinceId  = $request->integer('since_id', 0);
        $messages = $conversation->messages()
                                 ->with(['sender', 'attachments'])
                                 ->where('id', '>', $sinceId)
                                 ->orderBy('created_at')
                                 ->get();

        $this->service->markAsRead($conversation, $request->user());

        return MessageResource::collection($messages)->response();
    }
}
