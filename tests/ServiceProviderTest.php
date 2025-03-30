<?php

namespace Tests;

use Illuminate\Support\ServiceProvider;
use Laragear\Turnstile\Http\Middleware\InterstitialMiddleware;
use Laragear\Turnstile\Http\Middleware\TurnstileMiddleware;
use Laragear\Turnstile\Turnstile;
use Laragear\Turnstile\TurnstileServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function test_merges_config(): void
    {
        static::assertSame(
            $this->app->make('files')->getRequire(TurnstileServiceProvider::CONFIG),
            $this->app->make('config')->get('turnstile')
        );
    }

    public function test_loads_translations(): void
    {
        static::assertArrayHasKey('turnstile', $this->app->make('translator')->getLoader()->namespaces());
    }

    public function test_loads_views(): void
    {
        $hints = $this->app->make('view')->getFinder()->getHints();

        static::assertArrayHasKey('turnstile', $hints);
        static::assertSame([TurnstileServiceProvider::VIEWS], $hints['turnstile']);
    }

    public function test_registers_turnstile(): void
    {
        static::assertTrue($this->app->bound(Turnstile::class));
    }

    public function test_registers_middleware(): void
    {
        $middleware = $this->app->make('router')->getMiddleware();

        static::assertSame(TurnstileMiddleware::class, $middleware[TurnstileMiddleware::ALIAS]);
        static::assertSame(InterstitialMiddleware::class, $middleware[InterstitialMiddleware::ALIAS]);
    }

    public function test_publishes_config(): void
    {
        static::assertSame(
            [TurnstileServiceProvider::CONFIG => $this->app->configPath('turnstile.php')],
            ServiceProvider::pathsToPublish(TurnstileServiceProvider::class, 'config')
        );
    }

    public function test_publishes_translation(): void
    {
        static::assertSame(
            [TurnstileServiceProvider::LANG => $this->app->langPath('vendor/turnstile')],
            ServiceProvider::pathsToPublish(TurnstileServiceProvider::class, 'lang')
        );
    }

    public function test_publishes_views(): void
    {
        static::assertSame(
            [TurnstileServiceProvider::VIEWS => $this->app->resourcePath('vendor/turnstile')],
            ServiceProvider::pathsToPublish(TurnstileServiceProvider::class, 'views')
        );
    }

    public function test_registers_blade_components(): void
    {
        $namespaces = $this->app->make('blade.compiler')->getClassComponentNamespaces();
        static::assertArrayHasKey('turnstile', $namespaces);
        static::assertSame($namespaces['turnstile'], 'Laragear\\Turnstile\\Views\\Components');
    }
}
