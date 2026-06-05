<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $prefs = $request->user()->getOrCreateNotificationPreferences();

        return response()->json([
            'channels'        => $prefs->channels,
            'enabled_types'   => $prefs->enabled_types,
            'available_types' => config('notifications.types', []),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'channels'          => ['sometimes', 'array'],
            'channels.mail'     => ['boolean'],
            'channels.database' => ['boolean'],
            'enabled_types'     => ['sometimes', 'array'],
            'enabled_types.*'   => ['boolean'],
        ]);

        $prefs = $request->user()->getOrCreateNotificationPreferences();

        if ($request->has('channels')) {
            $prefs->update(['channels' => array_merge(
                $prefs->channels ?? [], $request->channels
            )]);
        }

        if ($request->has('enabled_types')) {
            $prefs->update(['enabled_types' => array_merge(
                $prefs->enabled_types ?? [], $request->enabled_types
            )]);
        }

        $prefs->refresh();

        return response()->json([
            'message'       => 'Préférences mises à jour.',
            'channels'      => $prefs->channels,
            'enabled_types' => $prefs->enabled_types,
        ]);
    }
}
