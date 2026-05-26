<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->isAgent() && ! $user->isAdmin())) {
            return response()->json(['message' => 'Accès réservé aux agents immobiliers.'], 403);
        }

        return $next($request);
    }
}
