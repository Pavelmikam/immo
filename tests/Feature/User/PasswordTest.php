<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_password_with_correct_current_password(): void
    {
        $user  = User::factory()->create(['password' => 'OldPassword1']);
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)->putJson('/api/user/password', [
            'current_password'      => 'OldPassword1',
            'password'              => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword1', $user->password));
    }

    public function test_returns_422_if_current_password_is_wrong(): void
    {
        $user  = User::factory()->create(['password' => 'OldPassword1']);
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)->putJson('/api/user/password', [
            'current_password'      => 'WrongPassword1',
            'password'              => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])->assertStatus(422)
          ->assertJsonStructure(['errors' => ['current_password']]);
    }

    public function test_revokes_other_tokens_after_password_change(): void
    {
        $user   = User::factory()->create(['password' => 'OldPassword1']);
        $token1 = $user->createToken('device-1')->plainTextToken;
        $user->createToken('device-2');

        $this->withToken($token1)->putJson('/api/user/password', [
            'current_password'      => 'OldPassword1',
            'password'              => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])->assertStatus(200);

        // Seul le token courant (device-1) doit rester en base
        $this->assertEquals(1, $user->tokens()->count());
    }
}
