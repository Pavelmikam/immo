<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $user->sendEmailVerificationNotification();

        $abilities = ['role:' . $user->role];
        $token = $user->createToken('api-token', $abilities)->plainTextToken;

        return (new AuthResource($user, $token))->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            return response()->json([
                'message' => 'Votre compte est suspendu. Contactez l\'administration.',
                'code'    => 'ACCOUNT_SUSPENDED',
            ], 403);
        }

        $user->tokens()->delete();

        $abilities = ['role:' . $user->role];
        $token = $user->createToken('api-token', $abilities)->plainTextToken;

        return (new AuthResource($user, $token))->response()->setStatusCode(200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return UserResource::make($request->user())->response()->setStatusCode(200);
    }

    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        if (! URL::hasValidSignature($request)) {
            return response()->json(['message' => 'Lien de vérification invalide ou expiré.'], 403);
        }

        $user = User::findOrFail($id);

        if ($user->isEmailVerified()) {
            return response()->json(['message' => 'Email déjà vérifié.'], 200);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email vérifié avec succès.'], 200);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        if ($request->user()->isEmailVerified()) {
            return response()->json(['message' => 'Email déjà vérifié.'], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Email de vérification renvoyé.'], 200);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(['email' => $request->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Lien de réinitialisation envoyé par email.'], 200);
        }

        return response()->json(['message' => __($status)], 400);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->update(['password' => $password]);
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mot de passe réinitialisé avec succès.'], 200);
        }

        return response()->json(['message' => 'Token invalide ou expiré.'], 400);
    }
}
