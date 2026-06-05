<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->boolean('unread')
            ? $request->user()->unreadNotifications()
            : $request->user()->notifications();

        if ($request->filled('type')) {
            $query->where('data->type', $request->type);
        }

        return NotificationResource::collection(
            $query->latest()->paginate(20)
        )->response();
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()
                                ->notifications()
                                ->findOrFail($notificationId);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marquée comme lue.',
            'data'    => new NotificationResource($notification->fresh()),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'Toutes les notifications marquées comme lues.',
            'count'   => 0,
        ]);
    }

    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()
                                ->notifications()
                                ->findOrFail($notificationId);

        $notification->delete();

        return response()->json(null, 204);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
