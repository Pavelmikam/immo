<?php

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'statut' => fake()->randomElement(['ouverte', 'ouverte', 'ouverte', 'fermee', 'archivee']),
        ];
    }

    public function ouverte(): static
    {
        return $this->state(['statut' => 'ouverte']);
    }
}
