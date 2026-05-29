<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'property_id'          => Property::factory(),
            'initiated_by'         => User::factory()->create(['role' => 'locataire', 'email_verified_at' => now(), 'is_active' => true])->id,
            'rental_request_id'    => null,
            'subject'              => null,
            'last_message_preview' => null,
            'last_message_at'      => null,
            'last_message_by'      => null,
            'is_archived'          => false,
        ];
    }

    public function withLastMessage(): static
    {
        return $this->state([
            'last_message_preview' => fake()->sentence(),
            'last_message_at'      => now()->subMinutes(rand(1, 1440)),
        ]);
    }
}
