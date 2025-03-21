<?php

namespace Tests\Views\Components;

use Laragear\Turnstile\Turnstile;
use Tests\TestCase;

class WidgetTest extends TestCase
{
    public function test_renders_with_defaults(): void
    {
        $this->blade('<x-turnstile::widget />')
            ->assertSee(
                '<div class="cf-turnstile" data-sitekey="' . Turnstile::SITE_KEY . '"></div>', false
            );
    }

    public function test_doesnt_render_if_turnstile_disabled(): void
    {
        $this->app->make('config')->set('turnstile.env', false);

        $this->blade('<x-turnstile::widget />')
            ->assertViewEmpty();
    }

    public function test_uses_attributes(): void
    {
        $this->blade('<x-turnstile::widget data-test="test"/>')
            ->assertSee(
                '<div class="cf-turnstile" data-sitekey="' . Turnstile::SITE_KEY . '" data-test="test"></div>', false
            );
    }
}
