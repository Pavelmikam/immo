<?php

namespace Database\Factories;

use App\Models\AmenityCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class AmenityCategoryFactory extends Factory
{
    protected $model = AmenityCategory::class;

    public function definition(): array
    {
        return [
            'category'   => fake()->randomElement(['property_type', 'amenity', 'charge']),
            'value'      => fake()->unique()->lexify('amenity_????'),
            'label'      => fake()->words(2, true),
            'is_active'  => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
