<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_returns_true_when_role_is_admin(): void
    {
        $user = User::factory()->admin()->make();
        $this->assertTrue($user->isAdmin());
    }

    public function test_is_proprietaire_returns_true_when_role_is_proprietaire(): void
    {
        $user = User::factory()->proprietaire()->make();
        $this->assertTrue($user->isProprietaire());
    }

    public function test_is_locataire_returns_true_when_role_is_locataire(): void
    {
        $user = User::factory()->locataire()->make();
        $this->assertTrue($user->isLocataire());
    }

    public function test_is_suspended_returns_true_when_is_active_is_false(): void
    {
        $user = User::factory()->suspended()->make();
        $this->assertTrue($user->isSuspended());
    }

    public function test_is_email_verified_returns_true_when_email_verified_at_is_set(): void
    {
        $user = User::factory()->make(['email_verified_at' => now()]);
        $this->assertTrue($user->isEmailVerified());
    }

    public function test_password_is_automatically_hashed_via_cast(): void
    {
        $user = User::factory()->create(['password' => 'plaintext']);
        $this->assertNotEquals('plaintext', $user->password);
        $this->assertTrue(Hash::check('plaintext', $user->password));
    }

    public function test_avatar_url_returns_null_when_no_avatar(): void
    {
        $user = User::factory()->make(['avatar_path' => null]);
        $this->assertNull($user->avatar_url);
    }

    public function test_avatar_url_returns_full_url_when_avatar_path_is_set(): void
    {
        Storage::fake('public');
        $user = User::factory()->make(['avatar_path' => 'avatars/1/profile.webp']);
        $this->assertStringContainsString('avatars/1/profile.webp', $user->avatar_url);
    }

    public function test_soft_delete_does_not_remove_user_from_database(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertNotNull(User::withTrashed()->find($user->id));
        $this->assertNull(User::find($user->id));
    }
}
