<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyImageFactory extends Factory
{
    protected $model = PropertyImage::class;

    public function definition(): array
    {
        $id        = fake()->numberBetween(1, 999);
        $timestamp = now()->timestamp . '_' . fake()->unique()->numerify('####');

        return [
            'property_id'    => Property::factory(),
            'original_path'  => "properties/{$id}/original_{$timestamp}.webp",
            'optimized_path' => "properties/{$id}/optimized_{$timestamp}.webp",
            'thumbnail_path' => "properties/{$id}/thumb_{$timestamp}.webp",
            'order'          => 1,
            'is_primary'     => false,
            'caption'        => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(['is_primary' => true]);
    }
}
