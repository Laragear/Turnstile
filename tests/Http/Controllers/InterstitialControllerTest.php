<?php

namespace Tests\Http\Controllers;

use Illuminate\Support\DateFactory;
use Laragear\Turnstile\Http\Controllers\InterstitialController;
use Laragear\Turnstile\Turnstile;
use Tests\TestCase;
use function now;

class InterstitialControllerTest extends TestCase
{
    protected function defineRoutes($router)
    {
        $router->middleware('web')->group(function () {
            InterstitialController::register();
        });
    }

    public function test_shows_with_default_view(): void
    {
        $this->get('turnstile/interstitial')
            ->assertOk()
            ->assertViewIs('turnstile::interstitial');
    }

    public function test_shows_with_custom_view(): void
    {
        $this->app->make('view')->addNamespace('test', __DIR__ . '/../../../resources/views');

        $this->app->make('config')->set('turnstile.interstitial.view', 'test::interstitial');

        $this->get('turnstile/interstitial')
            ->assertOk()
            ->assertViewIs('test::interstitial');
    }

    public function test_doesnt_show_if_challenge_is_recent(): void
    {
        $this->session([
            '_turnstile' => [
                'interstitial' => true
            ],
            'url' => [
                'intended' => 'http://localhost/test'
            ]
        ]);

        $this->get('turnstile/interstitial')
            ->assertRedirect('/test');
    }

    public function test_doesnt_show_if_challenge_is_future(): void
    {
        $this->session([
            '_turnstile' => [
                'interstitial' => $this->app->make(DateFactory::class)->now()->addMinute()->getTimestamp()
            ],
            'url' => [
                'intended' => 'http://localhost/test'
            ]
        ]);

        $this->get('turnstile/interstitial')
            ->assertRedirect('/test');
    }

    public function test_show_if_challenge_is_past(): void
    {
        $this->session([
            '_turnstile' => [
                'interstitial' => $this->app->make(DateFactory::class)->now()->subSecond()->getTimestamp()
            ],
        ]);

        $this->get('turnstile/interstitial')->assertOk();
    }

    public function test_allow_sets_session(): void
    {
        $this->session([
            'url' => [
                'intended' => 'http://localhost/test'
            ]
        ]);

        $this->post('turnstile/interstitial', [Turnstile::KEY => 'test'])
            ->assertRedirect('/test')
            ->assertSessionHas('_turnstile.interstitial', true);
    }

    public function test_allows_sets_session_with_minutes(): void
    {
        $this->freezeSecond();

        $this->app->make('config')->set('turnstile.interstitial.duration', 60);

        $this->post('turnstile/interstitial', [Turnstile::KEY => 'test'])
            ->assertSessionHas('_turnstile.interstitial', now()->addMinutes(60)->getTimestamp());
    }

    public function test_allows_sets_session_with_custom_key(): void
    {
        $this->app->make('config')->set('turnstile.interstitial.key', 'test_key');

        $this->post('turnstile/interstitial', [Turnstile::KEY => 'test'])
            ->assertSessionHas('test_key', true);
    }

    public function test_allows_redirects_if_challenge_is_recent(): void
    {
        $this->session([
            '_turnstile' => [
                'interstitial' => true
            ],
            'url' => [
                'intended' => 'http://localhost/test'
            ]
        ]);

        $this->post('turnstile/interstitial')
            ->assertRedirect('/test');
    }

    public function test_allows_redirects_if_challenge_is_future(): void
    {
        $this->session([
            '_turnstile' => [
                'interstitial' => now()->addMinute()->getTimestamp()
            ],
            'url' => [
                'intended' => 'http://localhost/test'
            ]
        ]);

        $this->post('turnstile/interstitial')
            ->assertRedirect('/test');
    }

    public function test_allows_sets_session_if_challenge_is_past(): void
    {
        $this->session([
            '_turnstile' => [
                'interstitial' => now()->subSecond()->getTimestamp()
            ],
        ]);

        $this->post('turnstile/interstitial', [Turnstile::KEY => 'test'])
            ->assertSessionHas('_turnstile.interstitial', true);
    }
}
