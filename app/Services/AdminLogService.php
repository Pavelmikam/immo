<?php

namespace App\Services;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AdminLogService
{
    public function log(
        User $admin,
        string $action,
        ?Model $target = null,
        array $before = [],
        array $after = [],
        ?Request $request = null
    ): AdminLog {
        return AdminLog::create([
            'admin_id'      => $admin->id,
            'action'        => $action,
            'loggable_type' => $target ? get_class($target) : null,
            'loggable_id'   => $target?->id,
            'before'        => !empty($before) ? $before : null,
            'after'         => !empty($after)  ? $after  : null,
            'ip_address'    => $request?->ip(),
            'user_agent'    => $request?->userAgent(),
        ]);
    }
}
