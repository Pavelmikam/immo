<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Non authentifié.',
                'code'    => 'UNAUTHENTICATED',
            ], 401);
        }

        if ($user->role === 'admin' || in_array($user->role, $roles)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Accès non autorisé.',
            'code'    => 'FORBIDDEN',
        ], 403);
    }
}
