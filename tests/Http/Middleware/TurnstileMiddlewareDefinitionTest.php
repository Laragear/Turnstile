<?php

namespace Tests\Http\Middleware;

use Laragear\Turnstile\Http\Middleware\TurnstileMiddlewareDefinition as Definition;
use PHPUnit\Framework\TestCase;

class TurnstileMiddlewareDefinitionTest extends TestCase
{
    public function test_defines_empty_defaults(): void
    {
        static::assertSame('turnstile:,,,', (string) new Definition());
    }

    public function test_defines_input(): void
    {
        static::assertSame('turnstile:test,,,', (string) (new Definition())->input('test'));
    }

    public function test_defines_guards(): void
    {
        static::assertSame('turnstile:,null,,', (string) (new Definition())->auth());
        static::assertSame('turnstile:,foo,,', (string) (new Definition())->auth('foo'));
        static::assertSame('turnstile:,foo&bar,,', (string) (new Definition())->auth(['foo', 'bar']));
        static::assertSame('turnstile:,foo&bar,,', (string) (new Definition())->auth('foo', 'bar'));
    }

    public function test_defines_action(): void
    {
        static::assertSame('turnstile:,,foo,', (string) (new Definition())->action('foo'));
    }

    public function test_defines_accept_failure(): void
    {
        static::assertSame('turnstile:,,,true', (string) (new Definition())->acceptFailed());
    }
}
