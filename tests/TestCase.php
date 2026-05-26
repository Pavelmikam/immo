<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }
}
