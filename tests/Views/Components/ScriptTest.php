<?php

namespace Tests\Views\Components;

class ScriptTest extends TestCase
{
    public function test_renders_with_defaults(): void
    {
        $this->render('<x-turnstile::script />')
            ->assertSee(
                '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js"  defer async></script>', false
            );
    }

    public function test_doesnt_render_if_turnstile_disabled(): void
    {
        $this->app->make('config')->set('turnstile.env', false);

        $this->render('<x-turnstile::script />')
            ->assertViewEmpty();
    }

    public function test_disables_async(): void
    {
        $this->render('<x-turnstile::script :async=false/>')
            ->assertSee(
                '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js"  defer></script>', false
            );
    }

    public function test_disables_defer(): void
    {
        $this->render('<x-turnstile::script :defer="false"/>')
            ->assertSee(
                '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js"  async></script>', false
            );
    }

    public function test_uses_explicit(): void
    {
        $this->render('<x-turnstile::script :explicit="true"/>')
            ->assertSee(
                '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"  defer async></script>',
                false
            );
    }

    public function test_uses_onload(): void
    {
        $this->render('<x-turnstile::script onload="testCallback"/>')
            ->assertSee(
                '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=testCallback"  defer async></script>',
                false
            );
    }

    public function test_uses_explicit_and_onload(): void
    {
        $this->render('<x-turnstile::script :explicit="true" :onload="\'testCallback\'"/>')
            ->assertSee(
                '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=testCallback"  defer async></script>',
                false
            );
    }

    public function test_uses_attributes(): void
    {
        $this->render('<x-turnstile::script data-test="test"/>')
            ->assertSee(
                '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" data-test="test" defer async></script>',
                false
            );
    }
}
