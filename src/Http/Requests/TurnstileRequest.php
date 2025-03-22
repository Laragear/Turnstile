<?php

namespace Laragear\Turnstile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Turnstile;

class TurnstileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function rules(): array
    {
        return $this->container->make(Turnstile::class)->rules();
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
