<?php

namespace Tests\Http\Middleware;

use Closure;
use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Http\Middleware\TurnstileMiddleware;
use Laragear\Turnstile\Turnstile;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TurnstileMiddlewareTest extends TestCase
{
    protected function route(?Closure $uses = null, string $name = 'test', string $middleware = 'turnstile'): Route
    {
        $uses ??= function (Turnstile $turnstile) {
            return $turnstile->hasChallenge() ? $turnstile->challenge()->success : null;
        };

        return $this->app->make('router')->post($name, $uses)->middleware(['web', $middleware]);
    }

    protected static function successfulChallenge(): Challenge
    {
        return new Challenge(true, '', '', '', [], [], new Carbon());
    }

    protected static function failedChallenge(): Challenge
    {
        return new Challenge(false, '', '', '', [], [], new Carbon());
    }

    public function test_passes_if_disabled(): void
    {
        $this->route();

        $this->app->make('config')->set('turnstile.env', false);

        $this->post('test')->assertOk()->isEmpty();
    }

    public function test_passes_if_challenge_successful(): void
    {
        $this->route();

        $this->post('test', [Turnstile::KEY => 'test_key'])->assertOk()->assertSee('1');
    }

    public function test_throws_if_key_empty(): void
    {
        $this->route();

        $this->post('test', [Turnstile::KEY => ''])
            ->assertSessionHasErrors([
                Turnstile::KEY => 'The Cloudflare Turnstile challenge is invalid, absent, or has failed.',
            ])
            ->assertRedirect('/');
    }

    public static function provideScalarsNotString(): array
    {
        return [
            [true],
            [false],
            [null],
            [[]],
            [1],
            [0.1]
        ];
    }

    #[DataProvider('provideScalarsNotString')]
    public function test_throws_if_key_not_string(mixed $value): void
    {
        $this->route();

        $this->post('test', [Turnstile::KEY => $value])
            ->assertSessionHasErrors([
                Turnstile::KEY => 'The Cloudflare Turnstile challenge is invalid, absent, or has failed.',
            ])
            ->assertRedirect('/');
    }

    public function test_throws_if_challenge_fails(): void
    {
        $this->route();

        $this->mock(Turnstile::class, static function (MockInterface $mock): void {
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test_token', '127.0.0.1')->andReturn(static::failedChallenge());
        });

        $this->post('test', [Turnstile::KEY => 'test_token'])
            ->assertSessionHasErrors([
                Turnstile::KEY => 'The Cloudflare Turnstile challenge is invalid, absent, or has failed.',
            ])
            ->assertRedirect('/');
    }

    public function test_uses_custom_key(): void
    {
        $this->route(middleware: TurnstileMiddleware::input('test_key'));

        $this->mock(Turnstile::class, static function (MockInterface $mock): void {
            $challenge = static::successfulChallenge();

            $mock->expects('key')->never();
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test_token', '127.0.0.1')->andReturn($challenge);
            $mock->expects('hasChallenge')->andReturnTrue();
            $mock->expects('challenge')->andReturn($challenge);
        });

        $this->post('test', ['test_key' => 'test_token'])->assertOk()->assertSee('1');
    }

    public function test_bypass_middleware_on_default_auth(): void
    {
        $this->route(middleware: TurnstileMiddleware::auth());

        $this->be(new User());

        $this->mock(Turnstile::class, static function (MockInterface $mock): void {
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->never();
            $mock->expects('hasChallenge')->andReturnFalse();
        });

        $this->post('test')->assertOk()->isEmpty();
    }

    public function test_bypass_middleware_on_custom_auth(): void
    {
        $this->route(middleware: TurnstileMiddleware::auth('web'));

        $this->be(new User(), 'web');

        $this->mock(Turnstile::class, static function (MockInterface $mock): void {
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->never();
            $mock->expects('hasChallenge')->andReturnFalse();
        });

        $this->post('test')->assertOk()->isEmpty();
    }

    public function test_passes_on_same_action(): void
    {
        $this->route(middleware: TurnstileMiddleware::action('test_action'));

        $this->mock(Turnstile::class, static function (MockInterface $mock): void {
            $challenge = new Challenge(true, '', 'test_action', '', [], [], new Carbon());

            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test_token', '127.0.0.1')->andReturn($challenge);
            $mock->expects('hasChallenge')->andReturnTrue();
            $mock->expects('challenge')->andReturn($challenge);
        });

        $this->post('test', [Turnstile::KEY => 'test_token'])->assertOk()->isEmpty();
    }

    public function test_fails_on_different_action(): void
    {
        $this->route(middleware: TurnstileMiddleware::action('invalid_action'));

        $this->mock(Turnstile::class, static function (MockInterface $mock): void {
            $challenge = new Challenge(true, '', 'test_action', '', [], [], new Carbon());

            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test_token', '127.0.0.1')->andReturn($challenge);
            $mock->expects('hasChallenge')->never();
            $mock->expects('challenge')->never();
        });

        $this->post('test', [Turnstile::KEY => 'test_token'])
            ->assertSessionHasErrors([
                Turnstile::KEY => 'The Cloudflare Turnstile challenge is invalid, absent, or has failed.',
            ])
            ->assertRedirect('/');
    }

    public function test_allows_failed_response_to_go_through(): void
    {
        $this->route(middleware: TurnstileMiddleware::acceptFailed());

        $this->mock(Turnstile::class, static function (MockInterface $mock): void {
            $challenge = static::failedChallenge();

            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test_token', '127.0.0.1')->andReturn($challenge);
            $mock->expects('hasChallenge')->andReturnTrue();
            $mock->expects('challenge')->andReturn($challenge);
        });

        $this->post('test', [Turnstile::KEY => 'test_token'])->assertOk()->isEmpty();
    }
}
