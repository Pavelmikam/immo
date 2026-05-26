<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertStatus(204);
    }

    public function test_token_is_invalidated_after_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertStatus(204);

        // Vérifie que le token a bien été supprimé de la base
        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_returns_401_if_not_authenticated(): void
    {
        $this->postJson('/api/auth/logout')->assertStatus(401);
    }
}
