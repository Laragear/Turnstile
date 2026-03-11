<?php

namespace Laragear\Turnstile\Filament\Forms;

use Closure;
use Filament\Enums\ThemeMode;
use Filament\FilamentManager;
use Filament\Forms\Components\Field;
use Laragear\Turnstile\Turnstile;
use function app;

class TurnstileWidget extends Field
{
    protected string $view = 'turnstile::widget';

    protected bool | Closure $isRequired = true;

    protected array $dataAttributes = [];

    protected bool $implicit = false;

    /*
    |--------------------------------------------------------------------------
    | Boot & Configuration
    |--------------------------------------------------------------------------
    */

    public static function getDefaultName(): ?string
    {
        return app(Turnstile::class)->key();
    }

    public function configure(): static
    {
        parent::configure();

        $this->evaluate(function (FilamentManager $filament, Turnstile $turnstile) { // @phpstan-ignore-line
            $theme = $filament->getCurrentPanel()?->getDefaultThemeMode() ?? ThemeMode::System;

            if ($theme !== ThemeMode::System) {
                $this->dataAttributes['theme'] = $theme->value;
            }

            $this->dataAttributes['sitekey'] = $turnstile->getSiteKey();
        });

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Field name / response-field mapping
    |--------------------------------------------------------------------------
    */

    public function name(string $name): static
    {
        if ($name !== Turnstile::KEY) {
            $this->dataAttributes['field-name'] = $name;
        }

        return parent::name($name);
    }

    /*
    |--------------------------------------------------------------------------
    | Generic data-attribute fluent API
    |--------------------------------------------------------------------------
    */

    /**
     * Sets the data attribute (or property value) on the widget.
     *
     * @return $this
     */
    public function dataAttribute(string $name, mixed $value): static
    {
        $this->dataAttributes[$name] = $value;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Size
    |--------------------------------------------------------------------------
    */

    /**
     * Restore the default (normal) widget size.
     *
     * @return $this
     */
    public function normal(): static
    {
        unset($this->dataAttributes['size']);

        return $this;
    }

    /**
     * Flexible-width widget (stretches to the container).
     *
     * @return $this
     */
    public function flexible(): static
    {
        return $this->dataAttribute('size', 'flexible');
    }

    /**
     * Compact (invisible) widget mode.
     *
     * @return $this
     */
    public function compact(): static
    {
        return $this->dataAttribute('size', 'compact');
    }

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    */

    /**
     * Follow the browser color-scheme preference.
     *
     * @return $this
     */
    public function system(): static
    {
        unset($this->dataAttributes['theme']);

        return $this;
    }

    /**
     * Render the widget using light colors.
     *
     * @return $this
     */
    public function light(): static
    {
        return $this->dataAttribute('theme', 'light');
    }

    /**
     * Render the widget using dark colors.
     *
     * @return $this
     */
    public function dark(): static
    {
        return $this->dataAttribute('theme', 'dark');
    }

    /*
    |--------------------------------------------------------------------------
    | Appearance
    |--------------------------------------------------------------------------
    */

    /**
     * Always show the widget.
     *
     * @return $this
     */
    public function appearanceAlways(): static
    {
        unset($this->dataAttributes['appearance']);

        return $this;
    }

    /**
     * Show the widget only after the execute() JS call.
     *
     * @return $this
     */
    public function appearanceExecute(): static
    {
        return $this->dataAttribute('appearance', 'execute');
    }

    /**
     * Show the widget only when user interaction is required.
     *
     * @return $this
     */
    public function appearanceInteractionOnly(): static
    {
        return $this->dataAttribute('appearance', 'interaction-only');
    }

    /*
    |--------------------------------------------------------------------------
    | Execution
    |--------------------------------------------------------------------------
    */

    /**
     * Execute the challenge on render (default).
     *
     * @return $this
     */
    public function executionRender(): static
    {
        unset($this->dataAttributes['execution']);

        return $this;
    }

    /**
     * Defer execution until the execute() JS call is made.
     *
     * @return $this
     */
    public function executionExecute(): static
    {
        return $this->dataAttribute('execution', 'execute');
    }

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    */

    /**
     * Let Cloudflare auto-detect the visitor's language (default).
     *
     * @return $this
     */
    public function languageAuto(): static
    {
        unset($this->dataAttributes['language']);

        return $this;
    }

    /**
     * Pin the widget to a specific BCP 47 language tag.
     *
     * @return $this
     */
    public function language(string $lang): static
    {
        return $this->dataAttribute('language', $lang);
    }

    /**
     * Use the application locale configured in config/app.php.
     *
     * @return $this
     */
    public function languageApp(): static
    {
        return $this->language(app('config')->get('app.locale'));
    }

    /*
    |--------------------------------------------------------------------------
    | Miscellaneous widget options
    |--------------------------------------------------------------------------
    */

    /**
     * Set the tab-index order when pressing [TAB] of following the form inputs.
     *
     * @return $this
     */
    public function tabindex(int $tabindex): static
    {
        return $this->dataAttribute('tabindex', $tabindex);
    }

    /**
     * Name of a global JS function to call after a successful challenge.
     *
     * The Blade view always wires its own Alpine callback first; this option
     * lets callers chain an *additional* side effect callback if needed.
     *
     * @return $this
     */
    public function callback(string $functionName): static
    {
        return $this->dataAttribute('callback', $functionName);
    }

    /**
     * The action label sent alongside the token to Cloudflare for analytics.
     *
     * @return $this
     */
    public function actionName(string $action): static
    {
        return $this->dataAttribute('action', $action);
    }

    /*
    |--------------------------------------------------------------------------
    | Implicit vs. explicit rendering
    |--------------------------------------------------------------------------
    */

    /**
     * Switch to Cloudflare Turnstile's *implicit* rendering mode.
     *
     * In implicit mode the Turnstile JS script auto-discovers divs that carry
     * the `cf-turnstile` CSS class and renders them automatically.  The Blade
     * view registers named global callbacks so Alpine can still capture the
     * token and forward it to Livewire at submit-time.
     *
     * The default (explicit) mode uses turnstile.render() for better control.
     *
     * @return $this
     */
    public function implicit(bool $condition = true): static
    {
        $this->implicit = $condition;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation - token consumption guard
    |--------------------------------------------------------------------------
    */

    /**
     * Return the field's validation rules, with one critical exception:
     * skip all rules when the current request is a Livewire *live field update*
     * (a property-sync-only request carrying no method calls).
     *
     * Why this matters? What the hell are you doing?
     * ----------------
     * Cloudflare Turnstile tokens are **single-use**. When a sibling field in
     * the same form uses ->live() or ->live('blur'), Livewire fires a network
     * request to validate that field. Without this guard, every such request
     * would cause the Turnstile server-side rule to call Cloudflare's siteverify
     * endpoint and consume the token before the form is even submitted.  The
     * next actual submission would then receive an "invalid token" error.
     *
     * This guard complements the client-side protection in the Blade view,
     * where the token is injected into Livewire's commit payload only when the
     * commit contains at least one method call (i.e. a real form action).
     * Both layers working together guarantee that the token reaches Cloudflare
     * exactly once - on the final, successful form submission.
     */
    public function getValidationRules(): array
    {
        return $this->isLiveFieldUpdate() ? [] : parent::getValidationRules();
    }

    /**
     * Decide whether the current request is a Livewire *live-field update*.
     *
     * A request qualifies as a live-field update when:
     *   1. It carries the X-Livewire header (it is a Livewire AJAX request), AND
     *   2. None of the component payloads include any `calls` entries.
     *
     * A request that has at least one method call in any component payload is
     * treated as a form submission and validation proceeds normally.
     *
     * Note: a standard (non-Livewire) HTTP POST also falls through to normal
     * validation because the X-Livewire header is absent.
     */
    protected function isLiveFieldUpdate(): bool
    {
        /** @var \Illuminate\Http\Request $request */
        $request = app('request');

        // If this is a regular HTTP submission, always validate the challenge.
        if (! $request->hasHeader('X-Livewire')) {
            return false;
        }

        foreach ((array) $request->json('components', []) as $component) {
            // If the method call is detected -> treat it as form submission.
            if (! empty($component['calls'])) {
                return false;
            }
        }

        // Only property syncs present -> live-field update, skip.
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDataAttributes(): array
    {
        return $this->dataAttributes;
    }

    public function isImplicit(): bool
    {
        return $this->implicit;
    }

    public function isExplicit(): bool
    {
        return ! $this->isImplicit();
    }

    public function getSiteKey(): string
    {
        return $this->dataAttributes['sitekey'];
    }
}
