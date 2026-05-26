<?php

namespace App\Services;

use App\Contracts\ImageServiceInterface;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class ImageService implements ImageServiceInterface
{
    private string $avatarDisk;
    private string $mediaDisk;

    public function __construct()
    {
        $this->avatarDisk = config('app.avatar_disk', 'public');
        $this->mediaDisk  = config('app.media_disk', 'media');
    }

    public function uploadAvatar(UploadedFile $file, int $userId): array
    {
        $directory = "avatars/{$userId}";
        $timestamp = now()->timestamp;
        $mainPath  = "{$directory}/profile_{$timestamp}.webp";
        $thumbPath = "{$directory}/thumb_{$timestamp}.webp";

        $manager = new ImageManager(new GdDriver());

        $image = $manager->read($file->getRealPath());
        $image->cover(
            (int) config('app.avatar_width', 400),
            (int) config('app.avatar_width', 400)
        );
        Storage::disk($this->avatarDisk)->put($mainPath, $image->toWebp(85)->toString());

        $thumb = $manager->read($file->getRealPath());
        $thumb->cover(
            (int) config('app.avatar_thumb_width', 80),
            (int) config('app.avatar_thumb_width', 80)
        );
        Storage::disk($this->avatarDisk)->put($thumbPath, $thumb->toWebp(80)->toString());

        return [
            'path'       => $mainPath,
            'thumb_path' => $thumbPath,
        ];
    }

    public function deleteAvatar(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk($this->avatarDisk)->delete($user->avatar_path);
        }
        if ($user->avatar_thumb_path) {
            Storage::disk($this->avatarDisk)->delete($user->avatar_thumb_path);
        }
    }

    public function uploadPropertyImage(UploadedFile $file, int $propertyId, int $order = 0): array
    {
        $directory = "properties/{$propertyId}";
        $timestamp = now()->timestamp . '_' . uniqid();

        $originalPath   = "{$directory}/original_{$timestamp}.webp";
        $optimizedPath  = "{$directory}/optimized_{$timestamp}.webp";
        $thumbnailPath  = "{$directory}/thumb_{$timestamp}.webp";

        $manager = new ImageManager(new GdDriver());

        // Original — full size converted to WebP
        $original = $manager->read($file->getRealPath());
        Storage::disk($this->mediaDisk)->put($originalPath, $original->toWebp(90)->toString());

        // Optimized — max 1200px wide
        $optimized = $manager->read($file->getRealPath());
        $optimizeWidth = (int) config('app.property_image_optimize_width', 1200);
        if ($optimized->width() > $optimizeWidth) {
            $optimized->scaleDown(width: $optimizeWidth);
        }
        Storage::disk($this->mediaDisk)->put($optimizedPath, $optimized->toWebp(85)->toString());

        // Thumbnail — 400×300 cropped
        $thumbW = (int) config('app.property_image_thumb_width', 400);
        $thumbH = (int) config('app.property_image_thumb_height', 300);
        $thumbnail = $manager->read($file->getRealPath());
        $thumbnail->cover($thumbW, $thumbH);
        Storage::disk($this->mediaDisk)->put($thumbnailPath, $thumbnail->toWebp(80)->toString());

        return [
            'original_path'  => $originalPath,
            'optimized_path' => $optimizedPath,
            'thumbnail_path' => $thumbnailPath,
        ];
    }

    public function deletePropertyImage(string $path): void
    {
        Storage::disk($this->mediaDisk)->delete($path);
    }

    public function deleteAllPropertyImages(Property $property): void
    {
        foreach ($property->images()->withTrashed()->get() as $image) {
            $this->deletePropertyImage($image->original_path);
            $this->deletePropertyImage($image->optimized_path);
            $this->deletePropertyImage($image->thumbnail_path);
        }
    }
}
