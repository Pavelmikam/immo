<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_verification_email(): void
    {
        Notification::fake();

        $user  = User::factory()->unverified()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/email/resend')->assertStatus(200);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_returns_400_if_email_already_verified(): void
    {
        $user  = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/email/resend')->assertStatus(400);
    }
}
