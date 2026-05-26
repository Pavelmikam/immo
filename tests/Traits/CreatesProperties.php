<?php

namespace Tests\Traits;

use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

trait CreatesProperties
{
    protected function makeProprietaire(array $attrs = []): User
    {
        return User::factory()->proprietaire()->create($attrs);
    }

    protected function makeProperty(User $owner, array $attrs = []): Property
    {
        return Property::factory()->for($owner, 'owner')->create($attrs);
    }

    protected function makeActiveProperty(User $owner, array $attrs = []): Property
    {
        return Property::factory()->for($owner, 'owner')->active()->create($attrs);
    }

    protected function attachFakeImages(Property $property, int $count = 1): void
    {
        Storage::fake('media');

        for ($i = 1; $i <= $count; $i++) {
            $ts    = now()->timestamp . "_{$i}";
            $dir   = "properties/{$property->id}";

            Storage::disk('media')->put("{$dir}/original_{$ts}.webp", 'fake');
            Storage::disk('media')->put("{$dir}/optimized_{$ts}.webp", 'fake');
            Storage::disk('media')->put("{$dir}/thumb_{$ts}.webp", 'fake');

            PropertyImage::factory()->for($property)->create([
                'original_path'  => "{$dir}/original_{$ts}.webp",
                'optimized_path' => "{$dir}/optimized_{$ts}.webp",
                'thumbnail_path' => "{$dir}/thumb_{$ts}.webp",
                'order'          => $i,
                'is_primary'     => $i === 1,
            ]);
        }
    }

    protected function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }
}
