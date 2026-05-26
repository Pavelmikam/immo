<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->isEmailVerified()) {
            return response()->json([
                'message' => 'Veuillez vérifier votre adresse email avant de continuer.',
                'code'    => 'EMAIL_NOT_VERIFIED',
            ], 403);
        }

        return $next($request);
    }
}
