<?php

namespace Tests;

use Laragear\Turnstile\Facades\Turnstile;
use Laragear\Turnstile\TurnstileServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [TurnstileServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Turnstile' => Turnstile::class,
        ];
    }
}
