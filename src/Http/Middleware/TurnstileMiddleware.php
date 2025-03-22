<?php

namespace Laragear\Turnstile\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laragear\Turnstile\Turnstile;
use function explode;
use function is_string;

/**
 * @method static \Laragear\Turnstile\Http\Middleware\TurnstileMiddlewareDefinition auth($guards = null)
 * @method static \Laragear\Turnstile\Http\Middleware\TurnstileMiddlewareDefinition input(string $name)
 * @method static \Laragear\Turnstile\Http\Middleware\TurnstileMiddlewareDefinition acceptFailed()
 * @method static \Laragear\Turnstile\Http\Middleware\TurnstileMiddlewareDefinition action(string $action)
 * @method static \Laragear\Turnstile\Http\Middleware\TurnstileMiddlewareDefinition onPrecognitive()
 */
class TurnstileMiddleware
{
    /**
     * The name of the alias of the middleware
     *
     * @const string
     */
    public const ALIAS = 'turnstile';

    /**
     * Create a new Middleware instance.
     */
    public function __construct(protected Factory $auth, protected Translator $lang, protected Turnstile $turnstile)
    {
        // ...
    }

    /**
     * Handle the incoming request.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function handle(
        Request $request,
        Closure $next,
        string $key = '',
        string $guards = '',
        string $action = '',
        string $acceptFailed = '',
    ): mixed
    {
        $key = $key ?: $this->turnstile->key();

        $shouldContinue = $this->turnstile->isDisabled()
            || $this->bypassOnAuth(explode(',', $guards))
            || $this->challengeSuccessful($request, $key, $action)
            || Str::lower($acceptFailed) === 'true';

        return $shouldContinue
            ? $next($request)
            : throw ValidationException::withMessages([$key => $this->lang->get('turnstile::validation.invalid')]);
    }

    /**
     * Check if the user is authenticated by the given guards.
     *
     * @param  string[]  $guards
     */
    protected function bypassOnAuth(array $guards): bool
    {
        foreach ($guards as $guard) {
            if ($this->auth->guard($guard === 'null' ? null : $guard)->check()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves and checks if the Turnstile Challenge is successful.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    protected function challengeSuccessful(Request $request, string $key, string $action): bool
    {
        $token = $request->input($key);

        if (is_string($token) && $token !== '') {
            $challenge = $this->turnstile->getChallenge($token, $request->ip());

            return $challenge->successful
                && (empty($action) || $challenge->isAction($action));
        }

        return false;
    }

    /**
     * Dynamically call methods to the underlying Turnstile Middleware Definition.
     */
    public static function __callStatic(string $name, array $arguments): TurnstileMiddlewareDefinition
    {
        return (new TurnstileMiddlewareDefinition())->{$name}(...$arguments);
    }
}
