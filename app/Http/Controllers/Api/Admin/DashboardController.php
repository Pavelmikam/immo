<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $stats = [
            'users' => [
                'total'          => User::count(),
                'locataires'     => User::where('role', 'locataire')->count(),
                'proprietaires'  => User::where('role', 'proprietaire')->count(),
                'admins'         => User::where('role', 'admin')->count(),
                'active'         => User::where('is_active', true)->count(),
                'suspended'      => User::where('is_active', false)->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->count(),
            ],
            'properties' => [
                'total'    => Property::withTrashed()->count(),
                'active'   => Property::where('status', 'active')->count(),
                'pending'  => Property::where('status', 'pending')->count(),
                'draft'    => Property::where('status', 'draft')->count(),
                'rejected' => Property::where('status', 'rejected')->count(),
                'archived' => Property::where('status', 'archived')->count(),
            ],
            'rental_requests' => [
                'total'      => RentalRequest::count(),
                'en_attente' => RentalRequest::where('status', 'en_attente')->count(),
                'acceptees'  => RentalRequest::where('status', 'acceptee')->count(),
                'refusees'   => RentalRequest::where('status', 'refusee')->count(),
                'annulees'   => RentalRequest::where('status', 'annulee')->count(),
            ],
            'conversations' => [
                'total'          => Conversation::count(),
                'messages_today' => Message::whereDate('created_at', today())->count(),
            ],
            'reports' => [
                'total'               => Report::count(),
                'pending'             => Report::where('status', 'en_attente')->count(),
                'resolved_this_month' => Report::whereIn('status', ['resolu', 'rejete'])
                                               ->whereMonth('handled_at', now()->month)
                                               ->count(),
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        return response()->json($stats);
    }
}
