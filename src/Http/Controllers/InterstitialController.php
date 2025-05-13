<?php

namespace Laragear\Turnstile\Http\Controllers;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Support\DateFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route as RouteFacade;
use Laragear\Turnstile\Http\Requests\TurnstileRequest;

class InterstitialController extends Controller
{
    /**
     * Create a new Controller instance.
     */
    public function __construct(
        protected Repository $config,
        protected DateFactory $date,
        protected Redirector $redirect,
    ) {
        $this->middleware(function (Request $request, Closure $next): mixed {
            // If the Request already contains the challenge, bail out.
            $duration = $request->session()->get($this->config->get('turnstile.interstitial.key'));

            if ($duration === true || $this->date->createFromTimestamp((int) $duration)->isFuture()) {
                return $this->redirect->intended();
            }

            return $next($request);
        });
    }

    /**
     * Show the interstitial form.
     */
    public function show(Repository $config, Factory $viewFactory): View
    {
        return $viewFactory->make($config->get('turnstile.interstitial.view'));
    }

    /**
     * Receive the interstitial challenge, and allow the user to continue.
     */
    public function allow(TurnstileRequest $request): RedirectResponse
    {
        $duration = $this->config->get('turnstile.interstitial.duration');

        // Add the session key with the timestamp for when the challenge should be forgotten.
        $request->session()->put(
            $this->config->get('turnstile.interstitial.key'),
            $duration === true ?: $this->date->now()->addMinutes($duration)->getTimestamp()
        );

        return $this->redirect->intended();
    }

    /**
     * Registers a default route for the controller to work.
     */
    public static function register(string $path = 'turnstile/interstitial', string|array $middleware = []): Route
    {
        $route = RouteFacade::get($path)
            ->uses([InterstitialController::class, 'show'])
            ->name(Config::get('turnstile.interstitial.route'))
            ->middleware($middleware);

        RouteFacade::post($path)->uses([InterstitialController::class, 'allow'])->middleware($middleware);

        return $route;
    }
}

