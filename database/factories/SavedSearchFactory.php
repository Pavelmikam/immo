<?php

namespace Database\Factories;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedSearchFactory extends Factory
{
    protected $model = SavedSearch::class;

    public function definition(): array
    {
        return [
            'user_id'               => User::factory(),
            'name'                  => fake()->words(3, true),
            'criteria'              => [
                'city'      => fake()->randomElement(['Yaoundé', 'Douala', 'Bafoussam']),
                'type'      => fake()->randomElement(['studio', 'apartment']),
                'price_max' => fake()->randomElement([75000, 100000, 150000]),
            ],
            'notifications_enabled' => true,
            'last_notified_at'      => null,
        ];
    }

    public function withNotificationsDisabled(): static
    {
        return $this->state(['notifications_enabled' => false]);
    }
}
