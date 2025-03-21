<?php

namespace Tests\Views\Components;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laragear\Turnstile\Enums\SiteKey;
use PHPUnit\Framework\Attributes\DataProvider;

class WidgetTest extends TestCase
{
    public function test_renders_with_defaults(): void
    {
        $this->render('<x-turnstile::widget />')
            ->assertSee(
                '<div class="cf-turnstile" data-sitekey="' . SiteKey::VisiblePassing->value . '"></div>', false
            );
    }

    public function test_doesnt_render_if_turnstile_disabled(): void
    {
        $this->app->make('config')->set('turnstile.env', false);

        $this->render('<x-turnstile::widget />')
            ->assertViewEmpty();
    }

    public function test_uses_attributes(): void
    {
        $this->render('<x-turnstile::widget data-test="test"/>')
            ->assertSee(
                '<div class="cf-turnstile" data-sitekey="' . SiteKey::VisiblePassing->value . '" data-test="test"></div>', false
            );
    }

    public function test_class_does_not_overwrite_base(): void
    {
        $this->render('<x-turnstile::widget data-test="test" class="foo"/>')
            ->assertSee(
                '<div class="cf-turnstile foo" data-sitekey="' . SiteKey::VisiblePassing->value . '" data-test="test"></div>',
                false
            );
    }

    public static function provideSiteKeysValues(): array
    {
        return Collection::make(SiteKey::cases())
            ->flatMap(static function (SiteKey $siteKey): array {
                return [
                    [Str::kebab($siteKey->name), $siteKey->value],
                    [Str::snake($siteKey->name), $siteKey->value],
                    [Str::studly($siteKey->name), $siteKey->value],
                ];
            })->toArray();
    }

    #[DataProvider('provideSiteKeysValues')]
    public function test_uses_custom_site_key(string $name, string $key): void
    {
        $this->render("<x-turnstile::widget site-key=\"$name\"/>")
            ->assertSee("<div class=\"cf-turnstile\" data-sitekey=\"$key\"></div>", false);
    }
}
