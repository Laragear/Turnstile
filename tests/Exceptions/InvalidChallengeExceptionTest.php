<?php

namespace Tests\Exceptions;

use Illuminate\Support\Carbon;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Exceptions\InvalidChallengeException;
use PHPUnit\Framework\TestCase;

class InvalidChallengeExceptionTest extends TestCase
{
    public function test_returns_same_challenge(): void
    {
        $challenge = new Challenge(true, '', '', '', [], [], new Carbon());

        $exception = new InvalidChallengeException($challenge);

        static::assertSame($challenge, $exception->getChallenge());
    }
}
