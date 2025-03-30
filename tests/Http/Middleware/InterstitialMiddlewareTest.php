<?php

namespace Tests\Http\Middleware;

use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Router;
use Illuminate\Support\DateFactory;
use Laragear\Turnstile\Http\Controllers\InterstitialController;
use Tests\TestCase;

class InterstitialMiddlewareTest extends TestCase
{
    protected function defineRoutes($router)
    {
        InterstitialController::register();
    }

    protected function router(): Router
    {
        return $this->app->make('router');
    }

    public function test_redirects_to_interstitial_when_no_challenge(): void
    {
        $this->router()->get('test/intended')->middleware('web', 'turnstile.interstitial');

        $this->get('test/intended')
            ->assertRedirect('turnstile/interstitial')
            ->assertRedirectToRoute($this->app->make('config')->get('turnstile.interstitial.route'))
            ->assertSessionHas('url.intended', 'http://localhost/test/intended');
    }

    public function test_doesnt_redirect_if_user_authenticated_by_default_guard(): void
    {
        $this->be(new User());

        $this->router()->get('test/intended', fn() => 'ok')->middleware('web', 'turnstile.interstitial:auth');

        $this->get('test/intended')->assertOk();
    }

    public function test_doesnt_redirect_if_user_authenticated_by_custom_guard(): void
    {
        $this->be(new User(), 'web');

        $this->router()->get('test/intended', fn() => 'ok')->middleware('web', 'turnstile.interstitial:auth=web');

        $this->get('test/intended')->assertOk();
    }

    public function test_doesnt_redirect_if_challenge_reminded(): void
    {
        $this->session([
            '_turnstile' => [
                'interstitial' => true
            ]
        ]);

        $this->router()->get('test/intended', fn() => 'ok')->middleware('web', 'turnstile.interstitial');

        $this->get('test/intended')->assertOk();
    }

    public function test_doesnt_redirect_if_challenge_timestamp_is_future(): void
    {
        $this->session([
            '_turnstile' => [
                'interstitial' => $this->app->make(DateFactory::class)->now()->addMinute()->getTimestamp()
            ]
        ]);

        $this->router()->get('test/intended', fn() => 'ok')->middleware('web', 'turnstile.interstitial');

        $this->get('test/intended')->assertOk();
    }

    public function test_redirects_if_challenge_timestamp_is_past(): void
    {
        $this->session([
            '_turnstile' => [
                'interstitial' => $this->app->make(DateFactory::class)->now()->subSecond()->getTimestamp()
            ]
        ]);

        $this->router()->get('test/intended', fn() => 'ok')->middleware('web', 'turnstile.interstitial');

        $this->get('test/intended')
            ->assertRedirect('turnstile/interstitial')
            ->assertRedirectToRoute($this->app->make('config')->get('turnstile.interstitial.route'));
    }
}
