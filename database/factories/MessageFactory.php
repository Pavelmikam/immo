<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id'       => User::factory()->create(['role' => 'locataire', 'email_verified_at' => now(), 'is_active' => true])->id,
            'body'            => fake()->paragraph(),
            'type'            => 'text',
        ];
    }
}
