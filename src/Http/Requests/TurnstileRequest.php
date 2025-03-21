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
}
