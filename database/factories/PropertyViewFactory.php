<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyViewFactory extends Factory
{
    protected $model = PropertyView::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'user_id'     => null,
            'session_id'  => fake()->uuid(),
            'ip_address'  => fake()->ipv4(),
            'user_agent'  => fake()->userAgent(),
            'referrer'    => fake()->optional(0.3)->url(),
            'viewed_at'   => now()->subHours(rand(0, 720)),
        ];
    }
}
