<?php

namespace Laragear\Turnstile\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Turnstile;
use Throwable;
use function __;
use function app;
use function request;

trait InteractsWithTurnstile
{
    /**
     * Boots the trait.
     */
    public function bootInteractsWithTurnstile(): void
    {
        $this->withValidator(function (ValidatorContract $validator): void {
            $validator->after($this->validateTurnstile(...));
        });
    }

    /**
     * Check if the Turnstile challenge validation should be skipped.
     */
    protected function skipTurnstileValidation(): bool
    {
        return false;
    }

    /**
     * Validates the Turnstile captcha token provided in the request data.
     */
    protected function validateTurnstile(): void
    {
        if ($this->skipTurnstileValidation() || request()->isPrecognitive()) {
            return;
        }

        $turnstile = app(Turnstile::class);

        // If Turnstile is disabled, we don't need to use it at all.
        if ($turnstile->isDisabled()) {
            return;
        }

        if (!$token = $this->turnstileToken($turnstile->key())) {
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
     * Return token for the Turnstile Challenge present in the form.
     *
     * @return string|null|void
     */
    protected function turnstileToken(string $key)
    {
        return Arr::get($this->data ?? $this->form?->getFormSnapshot(), $key);
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
        $this->notifyFailedTurnstileChallenge($challenge);
        $this->throwTurnstileValidationError($challenge);
    }

    /**
     * Send a notification to the user if the challenge has failed.
     */
    protected function notifyFailedTurnstileChallenge(?Challenge $challenge = null): void
    {
        Notification::make('turnstile-challenge')
            ->title(__('turnstile::notification.failed.title'))
            ->body(__('turnstile::notification.failed.body'))
            ->danger()
            ->send();
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
