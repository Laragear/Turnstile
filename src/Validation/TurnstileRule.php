<?php

namespace Laragear\Turnstile\Validation;

use Illuminate\Contracts\Auth\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laragear\Turnstile\Turnstile;
use Stringable;
use function array_pad;
use function in_array;
use function is_string;

/**
 * @internal
 */
class TurnstileRule
{
    /**
     * The name of the Turnstile rule.
     */
    public const string NAME = 'turnstile';

    /**
     * Create a new Rule instance.
     */
    public function __construct(protected Factory $auth, protected Turnstile $turnstile, protected Request $request)
    {
        //
    }

    /**
     * Validate that an attribute is a valid Cloudflare Turnstile response and challenge.
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function validate(string $attribute, mixed $value, array $parameters): bool
    {
        if ($this->turnstile->isDisabled()) {
            return true;
        }

        [$guards, $acceptFailed] = $this->parseParameters($parameters);

        if ($this->userIsAuthenticated($guards)) {
            return true;
        }

        return $this->hasToken($value)
            && ($this->turnstile->getChallenge($value, $this->request->ip())->successful || $acceptFailed);
    }

    /**
     * Parse the incoming rule parameters.
     *
     * @param  string[]  $parameters
     * @return array{0: string[], 1: bool}
     */
    protected function parseParameters(array $parameters): array
    {
        $parameters = array_pad($parameters, 2, 'null');

        return [
            $this->normalizeGuards($this->findAuthParameter($parameters)),
            in_array('accept-failed', $parameters, true),
        ];
    }

    /**
     * Find the parameter that has the auth configuration.
     *
     * @param  string[]  $parameters
     */
    protected function findAuthParameter(array $parameters): ?string
    {
        return Arr::first($parameters, static function (?string $parameter): bool {
            return Str::startsWith($parameter, 'auth');
        });
    }

    /**
     * Normalizes the guards from the auth parameter.
     *
     * @return array{0: null}|string[]
     */
    protected function normalizeGuards(?string $auth): array
    {
        return match ($auth) {
            null => [],
            'auth' => [null],
            default => Str::of($auth)->after('=')->explode(',')->toArray(),
        };
    }

    /**
     * Check if the user is authenticated on any given guard on the parameters.
     */
    protected function userIsAuthenticated(array $guards): bool
    {
        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the rule should run based on the guards and value.
     */
    protected function hasToken(mixed $value): bool
    {
        return (is_string($value) || $value instanceof Stringable) && !empty((string) $value);
    }
}
