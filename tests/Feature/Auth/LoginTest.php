<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create(['email' => 'user@example.cm', 'password' => 'Password1']);

        $this->postJson('/api/auth/login', [
            'email'    => 'user@example.cm',
            'password' => 'Password1',
        ])->assertStatus(200)
          ->assertJsonStructure(['token', 'token_type', 'user'])
          ->assertJsonPath('user.email', 'user@example.cm');
    }

    public function test_returns_401_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'user@example.cm', 'password' => 'Password1']);

        $this->postJson('/api/auth/login', [
            'email'    => 'user@example.cm',
            'password' => 'WrongPassword1',
        ])->assertStatus(401);
    }

    public function test_returns_401_with_unknown_email(): void
    {
        $this->postJson('/api/auth/login', [
            'email'    => 'nobody@example.cm',
            'password' => 'Password1',
        ])->assertStatus(401);
    }

    public function test_returns_403_if_account_is_suspended(): void
    {
        User::factory()->suspended()->create(['email' => 'banned@example.cm', 'password' => 'Password1']);

        $this->postJson('/api/auth/login', [
            'email'    => 'banned@example.cm',
            'password' => 'Password1',
        ])->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_SUSPENDED');
    }

    public function test_revokes_old_tokens_on_new_login(): void
    {
        $user = User::factory()->create(['email' => 'user@example.cm', 'password' => 'Password1']);

        $this->postJson('/api/auth/login', ['email' => 'user@example.cm', 'password' => 'Password1']);
        $this->postJson('/api/auth/login', ['email' => 'user@example.cm', 'password' => 'Password1']);

        $this->assertEquals(1, $user->tokens()->count());
    }
}
