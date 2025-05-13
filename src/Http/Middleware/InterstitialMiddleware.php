<?php

namespace Laragear\Turnstile\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\DateFactory;
use Illuminate\Support\Str;

class InterstitialMiddleware
{
    /**
     * The name of the middleware alias.
     *
     * @const string
     */
    public const ALIAS = TurnstileMiddleware::ALIAS . '.interstitial';

    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected Repository $config,
        protected Redirector $redirect,
        protected Factory $auth,
        protected DateFactory $date,
    ) {
        //
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next, string $auth = ''): mixed
    {
        if ($this->shouldSkipWhenAuthenticated($auth) || $this->shouldSkipWhenChallengeRecentlyDone($request)) {
            return $next($request);
        }

        $route = $this->config->get('turnstile.interstitial.route');

        if ($request->expectsJson()) {
            $this->throwJsonException($route);
        }

        return $this->redirect->setIntendedUrl($request->fullUrl())->route($route);
    }

    /**
     * Check if the user is authenticated when there is an auth parameter.
     */
    protected function shouldSkipWhenAuthenticated(string $auth): bool
    {
        foreach ($auth === 'auth' ? [null] : Str::of($auth)->after('=')->explode('&') as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the challenge was recently successful.
     */
    protected function shouldSkipWhenChallengeRecentlyDone(Request $request): bool
    {
        $duration = $request->session()->get($this->config->get('turnstile.interstitial.key'), 0);

        return $duration === true || $this->date->createFromTimestamp($duration)->isFuture();
    }

    /**
     * Throw a JSON exception with some data for the turnstile challenge.
     */
    protected function throwJsonException(string $route): never
    {
        throw new HttpResponseException(
            new JsonResponse([
                'success' => false,
                'message' => 'Requires Turnstile Challenge.',
                'redirect_url' => $this->redirect->route($route)->getTargetUrl(),
            ], 400)
        );
    }
}
