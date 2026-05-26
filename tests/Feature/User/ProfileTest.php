<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_authenticated_user_profile(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)
             ->getJson('/api/user/profile')
             ->assertStatus(200)
             ->assertJsonStructure(['id', 'name', 'email', 'role', 'avatar_url', 'avatar_thumb_url'])
             ->assertJsonMissing(['password']);
    }

    public function test_updates_user_profile(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)->putJson('/api/user/profile', [
            'name'  => 'Nouveau Nom',
            'phone' => '+237699000000',
            'city'  => 'Douala',
            'bio'   => 'Locataire sérieux.',
        ])->assertStatus(200)
          ->assertJsonPath('name', 'Nouveau Nom')
          ->assertJsonPath('city', 'Douala');
    }

    public function test_returns_401_if_not_authenticated(): void
    {
        $this->putJson('/api/user/profile', ['name' => 'Hacker'])->assertStatus(401);
    }
}
