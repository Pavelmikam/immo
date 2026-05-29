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

    /**
     * Reset the auth guard cache before every HTTP test request.
     *
     * Laravel's auth manager caches the resolved guard instance across
     * requests within the same test process. In production each request
     * is a fresh PHP-FPM worker, so this is never a problem. In tests,
     * when two requests with different tokens are made in the same test
     * method, the guard still holds the first user. Calling forgetGuards()
     * forces Sanctum to re-authenticate from the Bearer token on each call.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();
        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
