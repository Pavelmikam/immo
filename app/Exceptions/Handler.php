<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Non authentifié. Veuillez vous connecter.',
                    'code'    => 'UNAUTHENTICATED',
                ], 401);
            }
        });

        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Les données fournies sont invalides.',
                    'errors'  => $e->errors(),
                    'code'    => 'VALIDATION_ERROR',
                ], 422);
            }
        });

        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Ressource introuvable.',
                    'code'    => 'NOT_FOUND',
                ], 404);
            }
        });

        $this->renderable(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Action non autorisée.',
                    'code'    => 'FORBIDDEN',
                ], 403);
            }
        });

        if (app()->isProduction()) {
            $this->renderable(function (Throwable $e, Request $request) {
                if ($request->is('api/*') && ! ($e instanceof HttpException)) {
                    Log::error($e);
                    return response()->json([
                        'message' => 'Une erreur interne est survenue.',
                        'code'    => 'SERVER_ERROR',
                    ], 500);
                }
            });
        }
    }
}
