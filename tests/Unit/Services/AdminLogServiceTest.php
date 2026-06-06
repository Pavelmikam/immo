<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AdminLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_log_cree_entree_en_base(): void
    {
        $admin   = $this->makeAdmin();
        $target  = User::factory()->create(['role' => 'locataire']);
        $service = app(AdminLogService::class);

        $service->log($admin, 'user.suspend', $target, ['is_active' => true], ['is_active' => false]);

        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $admin->id,
            'action'   => 'user.suspend',
        ]);
    }

    public function test_log_sans_target_accepte(): void
    {
        $admin   = $this->makeAdmin();
        $service = app(AdminLogService::class);

        $service->log($admin, 'system.action');

        $this->assertDatabaseHas('admin_logs', [
            'admin_id'      => $admin->id,
            'action'        => 'system.action',
            'loggable_type' => null,
            'loggable_id'   => null,
        ]);
    }

    public function test_log_stocke_before_et_after(): void
    {
        $admin   = $this->makeAdmin();
        $target  = User::factory()->create(['role' => 'locataire']);
        $service = app(AdminLogService::class);

        $service->log($admin, 'user.suspend', $target, ['is_active' => true], ['is_active' => false]);

        $log = \App\Models\AdminLog::where('admin_id', $admin->id)->first();
        $this->assertEquals(['is_active' => true], $log->before);
        $this->assertEquals(['is_active' => false], $log->after);
    }

    public function test_log_sans_request_accepte(): void
    {
        $admin   = $this->makeAdmin();
        $target  = User::factory()->create(['role' => 'locataire']);
        $service = app(AdminLogService::class);

        $log = $service->log($admin, 'user.suspend', $target, [], [], null);

        $this->assertNull($log->ip_address);
        $this->assertNull($log->user_agent);
    }
}
