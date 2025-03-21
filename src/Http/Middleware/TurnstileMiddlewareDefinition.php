<?php

namespace Laragear\Turnstile\Http\Middleware;

use Illuminate\Support\Arr;
use Stringable;
use function implode;

class TurnstileMiddlewareDefinition implements Stringable
{
    /**
     * Create a new Middleware definition.
     */
    public function __construct(
        protected string $guards = '',
        protected string $input = '',
        protected string $failed = '',
        protected string $action = '',
    ) {
        // ...
    }

    /**
     * Bypass the middleware if the user is authenticated.
     *
     * @param  string  ...$guards
     * @return $this
     */
    public function auth(string|array $guards = 'null'): static
    {
        if (func_num_args() > 1) {
            $guards = func_get_args();
        }

        $this->guards = implode('&', Arr::wrap($guards));

        return $this;
    }

    /**
     * The input name in the request where the Cloudflare Turnstile response resides.
     *
     * @return $this
     */
    public function input(string $name): static
    {
        $this->input = $name;

        return $this;
    }

    /**
     * Makes an additional check for the action name of the Turnstile Challenge.
     *
     * @return $this
     */
    public function action(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Accept failed challenges that are no server-errors.
     *
     * @return $this
     */
    public function acceptFailed(): static
    {
        $this->failed = 'true';

        return $this;
    }

    /**
     * Returns the object instance as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'turnstile:' . implode(',', [
            $this->input,
            $this->guards,
            $this->action,
            $this->failed,
        ]);
    }
}
