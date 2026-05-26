<?php

namespace App\Contracts;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface ImageServiceInterface
{
    public function uploadAvatar(UploadedFile $file, int $userId): array;
    public function deleteAvatar(User $user): void;
    public function uploadPropertyImage(UploadedFile $file, int $propertyId, int $order = 0): array;
    public function deletePropertyImage(string $path): void;
    public function deleteAllPropertyImages(Property $property): void;
}
