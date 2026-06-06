<?php

namespace Tests\Feature\Admin;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLogTest extends TestCase
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

    private function createLog(User $admin, array $attrs = []): AdminLog
    {
        return AdminLog::create(array_merge([
            'admin_id'   => $admin->id,
            'action'     => 'user.suspend',
            'ip_address' => '127.0.0.1',
        ], $attrs));
    }

    public function test_admin_peut_lister_les_logs(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->createLog($admin);
        $this->createLog($admin);

        $this->withToken($token)->getJson('/api/admin/logs')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'meta']);
    }

    public function test_filtre_par_action(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $this->createLog($admin, ['action' => 'user.suspend']);
        $this->createLog($admin, ['action' => 'user.suspend']);
        $this->createLog($admin, ['action' => 'user.activate']);

        $response = $this->withToken($token)->getJson('/api/admin/logs?action=user.suspend');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filtre_par_date(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        $yesterday = $this->createLog($admin, ['action' => 'user.suspend']);
        \Illuminate\Support\Facades\DB::table('admin_logs')
            ->where('id', $yesterday->id)
            ->update(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

        $this->createLog($admin, ['action' => 'user.activate']);

        $today    = now()->toDateString();
        $response = $this->withToken($token)->getJson("/api/admin/logs?date_from={$today}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_logs_pagines_par_50(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFor($admin);

        for ($i = 0; $i < 55; $i++) {
            $this->createLog($admin);
        }

        $response = $this->withToken($token)->getJson('/api/admin/logs');

        $response->assertStatus(200);
        $this->assertCount(50, $response->json('data'));
        $this->assertEquals(55, $response->json('meta.total'));
    }

    public function test_non_admin_ne_peut_pas_voir_logs(): void
    {
        $tenant = User::factory()->create(['role' => 'locataire', 'is_active' => true]);
        $token  = $this->tokenFor($tenant);

        $this->withToken($token)->getJson('/api/admin/logs')->assertStatus(403);
    }
}
