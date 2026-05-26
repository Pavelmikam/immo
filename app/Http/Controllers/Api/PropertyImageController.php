<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ImageServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ReorderImagesRequest;
use App\Http\Requests\Property\UploadPropertyImageRequest;
use App\Http\Resources\Property\PropertyImageResource;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\JsonResponse;

class PropertyImageController extends Controller
{
    public function __construct(private ImageServiceInterface $imageService) {}

    public function store(UploadPropertyImageRequest $request, Property $property): JsonResponse
    {
        $this->authorize('uploadImage', $property);

        $order = $property->images()->max('order') + 1;

        $paths = $this->imageService->uploadPropertyImage(
            $request->file('image'),
            $property->id,
            $order
        );

        $isPrimary = $property->images()->count() === 0;

        $image = $property->images()->create([
            'original_path'  => $paths['original_path'],
            'optimized_path' => $paths['optimized_path'],
            'thumbnail_path' => $paths['thumbnail_path'],
            'order'          => $order,
            'is_primary'     => $isPrimary,
            'caption'        => $request->input('caption'),
        ]);

        return response()->json(new PropertyImageResource($image), 201);
    }

    public function destroy(Property $property, PropertyImage $propertyImage): JsonResponse
    {
        $this->authorize('deleteImage', $property);

        abort_unless($propertyImage->property_id === $property->id, 404);

        $this->imageService->deletePropertyImage($propertyImage->original_path);
        $this->imageService->deletePropertyImage($propertyImage->optimized_path);
        $this->imageService->deletePropertyImage($propertyImage->thumbnail_path);

        $propertyImage->delete();

        // If deleted image was primary, promote next image
        if ($propertyImage->is_primary) {
            $next = $property->images()->orderBy('order')->first();
            $next?->update(['is_primary' => true]);
        }

        return response()->json(null, 204);
    }

    public function reorder(ReorderImagesRequest $request, Property $property): JsonResponse
    {
        $this->authorize('reorderImages', $property);

        $imageIds = $request->input('order');

        foreach ($imageIds as $index => $imageId) {
            $property->images()
                ->where('id', $imageId)
                ->update(['order' => $index + 1]);
        }

        // First in new order becomes primary
        if (!empty($imageIds)) {
            $property->images()->update(['is_primary' => false]);
            $property->images()->where('id', $imageIds[0])->update(['is_primary' => true]);
        }

        return response()->json(
            PropertyImageResource::collection($property->fresh()->images),
            200
        );
    }
}
