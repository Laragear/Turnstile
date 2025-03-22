<?php

namespace Laragear\Turnstile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Turnstile;

class TurnstileRequest extends FormRequest
{
    /**
     * Validate the class instance.
     */
    public function validateResolved(): void
    {
        $this->checkTurnstileChallenge();

        parent::validateResolved();
    }

    /**
     * Checks if the Cloudflare Turnstile challenge is valid through the validation rule.
     *
     * @internal
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function checkTurnstileChallenge(): void
    {
        // Avoid depleting the challenge response if is precognitive.
        if ($this->isPrecognitive() && $this->skipChallengeWhenPrecognitive()) {
            return;
        }

        /** @var \Laragear\Turnstile\Turnstile $turnstile */
        $turnstile = $this->container->make(Turnstile::class);

        $key = $this->getTurnstileKey() ?: $turnstile->key();

        // Create a new validator with overridable the messages and attribute names.
        $this->container->make('validator')->make(
            $this->only($key),
            [$key => $this->getTurnstileRules() ?: $turnstile->rules()],
            $this->messages(),
            $this->attributes(),
        )->validate();
    }

    /**
     * Returns the default Turnstile Response token key to find in the request. When falsy, the default will be used.
     *
     * @return void|string
     */
    protected function getTurnstileKey()
    {
        // ...
    }

    /**
     * Returns the rules that will be used against the Turnstile Response token. When falsy, the defaults will be used.
     *
     * @return void|string|array<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    protected function getTurnstileRules()
    {
        // ...
    }

    /**
     * If the Precognitive Request should check for the Turnstile Challenge.
     */
    protected function skipChallengeWhenPrecognitive(): bool
    {
        return true;
    }

    /**
     * Returns the received Challenge data from Cloudflare Turnstile servers.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function challenge(): Challenge
    {
        return $this->container->make(Challenge::class);
    }

    /**
     * Returns a metadata value using a key in `dot.notation`.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function metadata(string $key, mixed $default = null): mixed
    {
        return $this->challenge()->metadata($key, $default);
    }

    /**
     * Check if the action is the same as the developer expects.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function isAction(string $action): bool
    {
        return $this->challenge()->isAction($action);
    }

    /**
     * Check if the action is not the same as the developer expects.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function isNotAction(string $action): bool
    {
        return $this->challenge()->isNotAction($action);
    }

    /**
     * Checks if the Customer Data is the same pattern as the developer expects.
     *
     * @param  string|iterable<string>  $customerData
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function isCustomerData(string|iterable $customerData): bool
    {
        return $this->challenge()->isCustomerData($customerData);
    }

    /**
     * Checks if the Customer Data is not the same pattern as the developer expects.
     *
     * @param  string|iterable<string>  $customerData
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function isNotCustomerData(string|iterable $customerData): bool
    {
        return $this->challenge()->isNotCustomerData($customerData);
    }
}
