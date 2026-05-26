<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_reset_link_for_existing_email(): void
    {
        User::factory()->create(['email' => 'user@example.cm']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'user@example.cm'])
             ->assertStatus(200)
             ->assertJsonPath('message', fn ($v) => str_contains($v, 'email'));
    }

    public function test_returns_422_for_unknown_email(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.cm'])
             ->assertStatus(422)
             ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_reset_password_with_valid_token(): void
    {
        $user  = User::factory()->create(['email' => 'user@example.cm']);
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token'                 => $token,
            'email'                 => 'user@example.cm',
            'password'              => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])->assertStatus(200);
    }
}
