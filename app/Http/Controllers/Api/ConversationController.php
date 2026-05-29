<?php

namespace App\Http\Controllers\Api;

use App\Contracts\MessagingServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Messaging\StartConversationRequest;
use App\Http\Resources\ConversationListResource;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(private MessagingServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $userId   = $request->user()->id;
        $archived = $request->boolean('archived', false);

        $conversations = Conversation::with([
                'property.primaryImage',
                'participants',
                'lastMessageByUser',
            ])
            ->forUser($userId)
            ->when(
                $archived,
                fn ($q) => $q->whereHas('participants', fn ($p) =>
                    $p->where('user_id', $userId)->where('is_archived', true)
                ),
                fn ($q) => $q->notArchivedForUser($userId)
            )
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return ConversationListResource::collection($conversations)
                   ->response()->setStatusCode(200);
    }

    public function store(StartConversationRequest $request, Property $property): JsonResponse
    {
        if (!$request->user()->isLocataire()) {
            return response()->json([
                'message' => 'Seuls les locataires peuvent initier une conversation.',
            ], 403);
        }

        try {
            $conversation = $this->service->findOrCreateConversation(
                $request->user(),
                $property,
                $request->validated('rental_request_id')
            );

            $this->service->sendMessage(
                $conversation,
                $request->user(),
                $request->validated('initial_message')
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $conversation->load(['property.primaryImage', 'participants', 'messages.sender']);

        return (new ConversationResource($conversation))
                   ->response()->setStatusCode(201);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $this->service->markAsRead($conversation, $request->user());

        $messages = $conversation->messages()
                                 ->with(['sender', 'attachments'])
                                 ->latest()
                                 ->paginate(30);

        $conversation->load(['property.primaryImage', 'participants']);

        return response()->json([
            'conversation' => new ConversationResource($conversation),
            'messages'     => MessageResource::collection($messages),
        ]);
    }

    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $this->service->markAsRead($conversation, $request->user());

        return response()->json([
            'message'      => 'Messages marqués comme lus.',
            'unread_count' => 0,
        ]);
    }

    public function archive(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('archive', $conversation);

        $this->service->archiveForUser($conversation, $request->user());

        return response()->json(['message' => 'Conversation archivée.']);
    }

    public function unarchive(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('archive', $conversation);

        $this->service->unarchiveForUser($conversation, $request->user());

        return response()->json(['message' => 'Conversation restaurée.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->service->getTotalUnread($request->user()),
        ]);
    }
}
