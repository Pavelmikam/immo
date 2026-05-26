<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'contenu' => fake('fr_FR')->paragraph(1),
            'lu'      => fake()->boolean(60),
        ];
    }

    public function nonLu(): static
    {
        return $this->state(['lu' => false]);
    }
}
