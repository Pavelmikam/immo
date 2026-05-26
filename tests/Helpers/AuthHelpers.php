<?php

namespace Tests\Helpers;

use App\Models\User;

function createUser(array $attrs = []): User
{
    return User::factory()->create($attrs);
}

function createAdmin(array $attrs = []): User
{
    return User::factory()->admin()->create($attrs);
}

function createVerifiedUser(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'email_verified_at' => now(),
    ], $attrs));
}
