<?php

namespace Laragear\Turnstile\Livewire;

use Illuminate\Validation\ValidationException;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Turnstile;
use Throwable;
use function __;
use function app;

trait InteractsWithTurnstile
{
    /**
     * The CloudFlare Turnstile Response token.
     */
    public ?string $cfTurnstileResponse = null;

    public function validate($rules = null, $messages = [], $attributes = [])
    {
        $validated = parent::validate($rules, $messages, $attributes);

        // If the validation passes, validate Turnstile automatically
        if ($this->validatesTurnstileAutomatically()) {
            $this->validateTurnstile();
        }

        return $validated;
    }

    /**
     * If the validation should be run automatically after validation.
     */
    protected function validatesTurnstileAutomatically(): bool
    {
        return true;
    }

    /**
     * Validates the Turnstile captcha token provided in the request data.
     */
    protected function validateTurnstile(): void
    {
        if ($this->skipTurnstileValidation()) {
            return;
        }

        $turnstile = app(Turnstile::class);

        // If Turnstile is disabled, we don't need to use it at all.
        if ($turnstile->isDisabled()) {
            return;
        }

        if (!$token = $this->turnstileToken()) {
            $this->handleFailedTurnstileChallenge();
        }

        try {
            $challenge = $this->retrieveTurnstileChallenge($turnstile, $token);
        } catch (Throwable $exception) {
            $this->handleTurnstileException($exception);
        }

        $challenge && $this->handleTurnstileChallengeStatus($challenge)
            ? $this->handleSuccessfulTurnstileChallenge($challenge)
            : $this->handleFailedTurnstileChallenge($challenge);
    }

    /**
     * Check if the Turnstile challenge validation should be skipped.
     */
    protected function skipTurnstileValidation(): bool
    {
        return false;
    }

    /**
     * Return token for the Turnstile Challenge present in the form.
     */
    protected function turnstileToken(): ?string
    {
        return $this->cfTurnstileResponse;
    }

    /**
     * Retrieves the token challenge.
     */
    protected function retrieveTurnstileChallenge(Turnstile $turnstile, string $token): ?Challenge
    {
        return $turnstile->getChallenge($token);
    }

    /**
     * Handles the Turnstile challenge and returns true or false if it has succeeded or failed.
     */
    protected function handleTurnstileChallengeStatus(Challenge $challenge): bool
    {
        return $challenge->successful;
    }

    /**
     * Handle a successful Turnstile challenge.
     */
    protected function handleSuccessfulTurnstileChallenge(Challenge $challenge): void
    {
        //
    }

    /**
     * Handle a failed Turnstile challenge.
     */
    protected function handleFailedTurnstileChallenge(?Challenge $challenge = null): void
    {
        $this->throwTurnstileValidationError($challenge);
    }

    /**
     * Throw a Validation exception for the failed Turnstile challenge.
     */
    protected function throwTurnstileValidationError(?Challenge $challenge = null): never
    {
        throw ValidationException::withMessages([
            Turnstile::KEY => __('turnstile::validation.invalid'),
        ]);
    }

    /**
     * Handle a Turnstile challenge exception.
     *
     * @return void|never
     */
    protected function handleTurnstileException(Throwable $exception)
    {
        throw $exception;
    }
}
