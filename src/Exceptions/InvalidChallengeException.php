<?php

namespace Laragear\Turnstile\Exceptions;

use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Contracts\TurnstileException;
use RuntimeException;
use function implode;

class InvalidChallengeException extends RuntimeException implements TurnstileException
{
    /**
     * Create a new Runtime Exception instance.
     */
    public function __construct(protected Challenge $challenge)
    {
        parent::__construct('The challenge is invalid: ' . implode(', ', $this->challenge->errors) . '.');
    }

    /**
     * Returns the offending challenge.
     */
    public function getChallenge(): Challenge
    {
        return $this->challenge;
    }
}
