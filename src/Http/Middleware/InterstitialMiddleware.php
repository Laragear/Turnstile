<?php

namespace Laragear\Turnstile\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\DateFactory;
use Illuminate\Support\Str;

/**
 * @internal
 */
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

        return $this->redirect
            ->setIntendedUrl($request->fullUrl())
            ->route($this->config->get('turnstile.interstitial.route'));
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
}
