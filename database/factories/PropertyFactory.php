<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory()->proprietaire(),
            'title'            => fake()->sentence(6),
            'description'      => fake()->paragraphs(3, true),
            'type'             => fake()->randomElement(['apartment', 'house', 'studio', 'villa', 'commercial', 'land']),
            'transaction_type' => fake()->randomElement(['rent', 'sale']),
            'price'            => fake()->numberBetween(50000, 5000000),
            'surface'          => fake()->numberBetween(20, 500),
            'rooms'            => fake()->numberBetween(1, 10),
            'bathrooms'        => fake()->numberBetween(1, 5),
            'address'          => fake()->streetAddress(),
            'city'             => fake()->randomElement(['Yaoundé', 'Douala', 'Bafoussam', 'Garoua']),
            'district'         => fake()->word(),
            'latitude'         => fake()->latitude(2, 6),
            'longitude'        => fake()->longitude(8, 16),
            'status'           => 'draft',
            'is_featured'      => false,
            'amenities'        => null,
            'available_from'   => null,
            'views_count'      => 0,
            'favorites_count'  => 0,
        ];
    }

    public function withAmenities(array $amenities): static
    {
        return $this->state(['amenities' => $amenities]);
    }

    public function withCoords(float $lat, float $lng): static
    {
        return $this->state(['latitude' => $lat, 'longitude' => $lng]);
    }

    public function active(): static
    {
        return $this->state([
            'status'       => 'active',
            'published_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'           => 'rejected',
            'rejection_reason' => 'Le bien ne répond pas aux critères de qualité.',
        ]);
    }

    public function archived(): static
    {
        return $this->state(['status' => 'archived']);
    }

    public function featured(): static
    {
        return $this->state([
            'status'       => 'active',
            'published_at' => now(),
            'is_featured'  => true,
        ]);
    }

    public function forRent(): static
    {
        return $this->state(['transaction_type' => 'rent']);
    }

    public function forSale(): static
    {
        return $this->state(['transaction_type' => 'sale']);
    }
}
