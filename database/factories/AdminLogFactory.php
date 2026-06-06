<?php

namespace Database\Factories;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminLogFactory extends Factory
{
    protected $model = AdminLog::class;

    public function definition(): array
    {
        return [
            'admin_id'      => User::factory()->create(['role' => 'admin'])->id,
            'action'        => fake()->randomElement(['user.suspend', 'user.activate', 'property.approve', 'report.resolve']),
            'loggable_type' => null,
            'loggable_id'   => null,
            'before'        => null,
            'after'         => null,
            'ip_address'    => fake()->ipv4(),
            'user_agent'    => fake()->userAgent(),
        ];
    }
}
