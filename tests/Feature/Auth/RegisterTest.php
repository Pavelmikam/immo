<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_register_as_locataire(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Jean Dupont',
            'email'                 => 'jean@example.cm',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'locataire',
        ]);

        $response->assertStatus(201)->assertJsonStructure(['token', 'token_type', 'user']);
        $this->assertEquals('locataire', User::where('email', 'jean@example.cm')->first()->role);
    }

    public function test_visitor_can_register_as_proprietaire(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', [
            'name'                  => 'Marie Nguema',
            'email'                 => 'marie@example.cm',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'proprietaire',
        ])->assertStatus(201);

        $this->assertEquals('proprietaire', User::where('email', 'marie@example.cm')->first()->role);
    }

    public function test_returns_422_if_email_already_taken(): void
    {
        User::factory()->create(['email' => 'taken@example.cm']);

        $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'taken@example.cm',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'locataire',
        ])->assertStatus(422)->assertJsonPath('errors.email.0', fn ($v) => str_contains($v, 'email'));
    }

    public function test_returns_422_if_passwords_do_not_match(): void
    {
        $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.cm',
            'password'              => 'Password1',
            'password_confirmation' => 'WrongPass1',
            'role'                  => 'locataire',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['password']]);
    }

    public function test_admin_role_is_forbidden_on_register(): void
    {
        $this->postJson('/api/auth/register', [
            'name'                  => 'Hacker',
            'email'                 => 'hack@example.cm',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'admin',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['role']]);
    }

    public function test_verification_email_sent_after_registration(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', [
            'name'                  => 'Paul Mbarga',
            'email'                 => 'paul@example.cm',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'locataire',
        ])->assertStatus(201);

        $user = User::where('email', 'paul@example.cm')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_returns_422_when_required_fields_missing(): void
    {
        $this->postJson('/api/auth/register', [])
             ->assertStatus(422)
             ->assertJsonStructure(['errors' => ['name', 'email', 'password', 'role']]);
    }

    public function test_accepts_valid_cameroonian_phone_number(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', [
            'name'                  => 'Alain Fotso',
            'email'                 => 'alain@example.cm',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'locataire',
            'phone'                 => '+237655123456',
        ])->assertStatus(201);

        $this->assertEquals('+237655123456', User::where('email', 'alain@example.cm')->first()->phone);
    }
}
