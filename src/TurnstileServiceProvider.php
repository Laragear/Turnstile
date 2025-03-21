<?php

namespace Laragear\Turnstile;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * @internal
 */
class TurnstileServiceProvider extends ServiceProvider
{
    // These constants point to publishable files/directories.
    public const CONFIG = __DIR__.'/../config/turnstile.php';
    public const LANG = __DIR__.'/../lang';

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(static::CONFIG, 'turnstile');
        $this->loadTranslationsFrom(static::LANG, 'turnstile');

        $this->app->singleton(Turnstile::class);

        // Remove the challenge when the application lifecycle ends.
        $this->app->terminating(static function (Application $app) {
            unset($app[Challenge::class]);
        });
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(Router $router): void
    {
        $router->aliasMiddleware(
            Http\Middleware\TurnstileMiddleware::ALIAS, Http\Middleware\TurnstileMiddleware::class
        );

        if ($this->app->runningInConsole()) {
            $this->publishes([static::CONFIG => $this->app->configPath('turnstile.php')], 'config');
            $this->publishes([static::LANG => $this->app->langPath('vendor/turnstile')], 'lang');
        }

        $this->callAfterResolving('blade.compiler', static function (BladeCompiler $blade): void {
            $blade->componentNamespace('Laragear\\Turnstile\\Views\\Components', 'turnstile');
        });

        $this->callAfterResolving('validator', static function (ValidatorFactory $validator, Application $app): void {
            $validator->extendImplicit(
                Validation\TurnstileRule::NAME,
                Validation\TurnstileRule::class,
                $app->make('translator')->get('turnstile::validation.invalid')
            );
        });
    }
}
