<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role'              => 'admin',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
    }

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'      => 'locataire',
            'is_active' => true,
        ], $attrs));
    }

    public function test_admin_peut_lister_les_utilisateurs(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->withToken($token)->getJson('/api/admin/users')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'meta']);
    }

    public function test_liste_inclut_les_soft_deleted(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);
        $user  = $this->makeUser();
        $user->delete();

        $response = $this->withToken($token)->getJson('/api/admin/users');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($user->id, $ids);
    }

    public function test_filtre_par_role(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        User::factory()->count(3)->create(['role' => 'locataire', 'is_active' => true]);
        User::factory()->count(2)->create(['role' => 'proprietaire', 'is_active' => true]);

        $response = $this->withToken($token)->getJson('/api/admin/users?role=locataire');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_filtre_par_is_active(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        User::factory()->count(2)->create(['role' => 'locataire', 'is_active' => true]);
        User::factory()->create(['role' => 'locataire', 'is_active' => false]);

        $response = $this->withToken($token)->getJson('/api/admin/users?is_active=false&role=locataire');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_recherche_par_nom(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->makeUser(['name' => 'Dupont Jean', 'role' => 'locataire']);
        $this->makeUser(['name' => 'Martin Pierre', 'role' => 'locataire']);

        $response = $this->withToken($token)->getJson('/api/admin/users?search=Dupont&role=locataire');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Dupont Jean', $response->json('data.0.name'));
    }

    public function test_admin_peut_voir_detail_utilisateur(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);
        $user  = $this->makeUser();

        $response = $this->withToken($token)->getJson("/api/admin/users/{$user->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role', 'properties_count']]);
    }

    public function test_admin_peut_suspendre_un_utilisateur(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);
        $user  = $this->makeUser();

        $this->withToken($token)->postJson("/api/admin/users/{$user->id}/suspend", [
            'reason' => 'Comportement abusif signalé par plusieurs utilisateurs.',
        ])->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
        $this->assertDatabaseHas('admin_logs', ['action' => 'user.suspend', 'admin_id' => $admin->id]);
    }

    public function test_admin_ne_peut_pas_suspendre_un_admin(): void
    {
        $admin  = $this->makeAdmin();
        $token  = $this->tokenFor($admin);
        $admin2 = $this->makeAdmin();

        $this->withToken($token)->postJson("/api/admin/users/{$admin2->id}/suspend", [
            'reason' => 'Test de suspension admin.',
        ])->assertStatus(422);
    }

    public function test_admin_ne_peut_pas_se_suspendre_soi_meme(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->withToken($token)->postJson("/api/admin/users/{$admin->id}/suspend", [
            'reason' => 'Auto-suspension test.',
        ])->assertStatus(422);
    }

    public function test_admin_peut_reactiver_un_compte_suspendu(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);
        $user  = $this->makeUser(['is_active' => false]);

        $this->withToken($token)->postJson("/api/admin/users/{$user->id}/activate")
             ->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => true]);
    }

    public function test_admin_peut_supprimer_utilisateur_soft(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);
        $user  = $this->makeUser();

        $this->withToken($token)->deleteJson("/api/admin/users/{$user->id}")
             ->assertStatus(204);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_admin_ne_peut_pas_supprimer_un_admin(): void
    {
        $admin  = $this->makeAdmin();
        $token  = $this->tokenFor($admin);
        $admin2 = $this->makeAdmin();

        $this->withToken($token)->deleteJson("/api/admin/users/{$admin2->id}")
             ->assertStatus(422);
    }

    public function test_admin_peut_restaurer_utilisateur_supprime(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);
        $user  = $this->makeUser();
        $user->delete();

        $this->withToken($token)->postJson("/api/admin/users/{$user->id}/restore")
             ->assertStatus(200);

        $this->assertNull(User::withTrashed()->find($user->id)->deleted_at);
    }

    public function test_suspension_log_admin_creee(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);
        $user  = $this->makeUser();

        $this->withToken($token)->postJson("/api/admin/users/{$user->id}/suspend", [
            'reason' => 'Activité suspecte détectée sur le compte.',
        ]);

        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $admin->id,
            'action'   => 'user.suspend',
        ]);
    }
}
