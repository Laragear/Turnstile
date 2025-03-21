<?php

namespace Tests\Facades;

use Laragear\Turnstile\Facades\Turnstile as TurnstileFacade;
use Laragear\Turnstile\Turnstile;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    public function test_facade_returns_turnstile(): void
    {
        static::assertInstanceOf(Turnstile::class, TurnstileFacade::getFacadeRoot());
    }
}
