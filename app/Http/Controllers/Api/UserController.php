<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ImageServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private ImageServiceInterface $imageService) {}

    public function profile(Request $request): JsonResponse
    {
        return UserResource::make($request->user())->response()->setStatusCode(200);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return UserResource::make($request->user()->fresh())->response()->setStatusCode(200);
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->imageService->deleteAvatar($user);

        $paths = $this->imageService->uploadAvatar($request->file('avatar'), $user->id);

        $user->update([
            'avatar_path'       => $paths['path'],
            'avatar_thumb_path' => $paths['thumb_path'],
        ]);

        return UserResource::make($user->fresh())->response()->setStatusCode(200);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['password' => $request->password]);

        $currentTokenId = $user->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json(['message' => 'Mot de passe modifié avec succès.'], 200);
    }
}
