<?php

namespace Tests\Filament\Forms;

use Closure;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Http\Request;
use Laragear\Turnstile\Filament\Forms\TurnstileWidget;
use Laragear\Turnstile\Turnstile;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Mockery\MockInterface;
use ReflectionMethod;
use Tests\TestCase;
use function class_exists;

class TurnstileWidgetTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FilamentServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            ActionsServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        $this->markTestSkippedUnless(class_exists(FilamentServiceProvider::class), 'Filament 5.x is not installed');

        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make(),
        ];

        parent::setUp();
    }

    public function test_default_name_matches_the_turnstile_canonical_key(): void
    {
        static::assertSame($this->app->make(Turnstile::class)->key(), TurnstileWidget::getDefaultName());
        static::assertSame(Turnstile::KEY, TurnstileWidget::getDefaultName());
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    */

    public function test_configure_populates_sitekey_in_data_attributes(): void
    {
        static::assertArrayHasKey('sitekey', $this->resolveWidget()->getDataAttributes());
    }

    public function test_get_site_key_matches_the_bound_turnstile_instance(): void
    {
        static::assertSame($this->app->make(Turnstile::class)->getSiteKey(), $this->resolveWidget()->getSiteKey());
    }

    public function test_configure_does_not_set_theme_when_panel_uses_system_theme(): void
    {
        static::assertArrayNotHasKey('theme', $this->resolveWidget()->getDataAttributes());
    }

    /*
    |--------------------------------------------------------------------------
    | Visibility
    |--------------------------------------------------------------------------
    */

    public function test_does_not_render_when_turnstile_is_disabled(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('key')->andReturn('test-key');
            $mock->expects('getSiteKey')->andReturn('test-key');
            $mock->expects('isDisabled')->andReturnTrue();
        });

        static::assertFalse(TurnstileWidget::make()->isVisible());
    }

    /*
    |--------------------------------------------------------------------------
    | Naming
    |--------------------------------------------------------------------------
    */

    public function test_custom_name_stores_field_name_in_data_attributes(): void
    {
        static::assertSame('my-captcha', TurnstileWidget::make('my-captcha')->getDataAttributes()['response-field-name']);
    }

    public function test_canonical_key_does_not_store_field_name_in_data_attributes(): void
    {
        static::assertArrayNotHasKey('field-name', TurnstileWidget::make()->getDataAttributes());
        static::assertArrayNotHasKey('field-name', TurnstileWidget::make(Turnstile::KEY)->getDataAttributes());
    }

    /*
    |--------------------------------------------------------------------------
    | Data Attribute method
    |--------------------------------------------------------------------------
    */

    public function test_data_attribute_stores_arbitrary_key_value_pair(): void
    {
        $widget = TurnstileWidget::make()->dataAttribute('something', 'else');

        static::assertSame('else', $widget->getDataAttributes()['something']);
    }

    public function test_data_attribute_overwrites_existing_key(): void
    {
        $widget = TurnstileWidget::make()
            ->dataAttribute('size', 'flexible')
            ->dataAttribute('size', 'compact');

        static::assertSame('compact', $widget->getDataAttributes()['size']);
    }

    /*
    |--------------------------------------------------------------------------
    | Size
    |--------------------------------------------------------------------------
    */

    public function test_flexible_sets_size_attribute_to_flexible(): void
    {
        $widget = TurnstileWidget::make()->flexible();

        static::assertSame('flexible', $widget->getDataAttributes()['size']);
    }

    public function test_compact_sets_size_attribute_to_compact(): void
    {
        $widget = TurnstileWidget::make()->compact();

        static::assertSame('compact', $widget->getDataAttributes()['size']);
    }

    public function test_normal_removes_size_attribute(): void
    {
        $widget = TurnstileWidget::make()->flexible()->normal();

        static::assertArrayNotHasKey('size', $widget->getDataAttributes());
    }

    public function test_normal_is_idempotent_when_size_was_never_set(): void
    {
        $widget = TurnstileWidget::make()->normal();

        static::assertArrayNotHasKey('size', $widget->getDataAttributes());
    }

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    */

    public function test_light_sets_theme_attribute_to_light(): void
    {
        $widget = TurnstileWidget::make()->light();

        static::assertSame('light', $widget->getDataAttributes()['theme']);
    }

    public function test_dark_sets_theme_attribute_to_dark(): void
    {
        $widget = TurnstileWidget::make()->dark();

        static::assertSame('dark', $widget->getDataAttributes()['theme']);
    }

    public function test_system_removes_theme_attribute(): void
    {
        $widget = TurnstileWidget::make()->dark()->system();

        static::assertArrayNotHasKey('theme', $widget->getDataAttributes());
    }

    public function test_system_is_idempotent_when_theme_was_never_set(): void
    {
        $widget = TurnstileWidget::make()->system();

        static::assertArrayNotHasKey('theme', $widget->getDataAttributes());
    }

    /*
    |--------------------------------------------------------------------------
    | Appearance
    |--------------------------------------------------------------------------
    */

    public function test_appearance_execute_sets_appearance_attribute(): void
    {
        $widget = TurnstileWidget::make()->appearanceExecute();

        static::assertSame('execute', $widget->getDataAttributes()['appearance']);
    }

    public function test_appearance_interaction_only_sets_appearance_attribute(): void
    {
        $widget = TurnstileWidget::make()->appearanceInteractionOnly();

        static::assertSame('interaction-only', $widget->getDataAttributes()['appearance']);
    }

    public function test_appearance_always_removes_appearance_attribute(): void
    {
        $widget = TurnstileWidget::make()->appearanceExecute()->appearanceAlways();

        static::assertArrayNotHasKey('appearance', $widget->getDataAttributes());
    }

    /*
    |--------------------------------------------------------------------------
    | Execution
    |--------------------------------------------------------------------------
    */

    public function test_execution_execute_sets_execution_attribute(): void
    {
        $widget = TurnstileWidget::make()->executionExecute();

        static::assertSame('execute', $widget->getDataAttributes()['execution']);
    }

    public function test_execution_render_removes_execution_attribute(): void
    {
        $widget = TurnstileWidget::make()->executionExecute()->executionRender();

        static::assertArrayNotHasKey('execution', $widget->getDataAttributes());
    }

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    */

    public function test_language_sets_language_attribute_to_given_bcp47_tag(): void
    {
        $widget = TurnstileWidget::make()->language('pt-BR');

        static::assertSame('pt-BR', $widget->getDataAttributes()['language']);
    }

    public function test_language_auto_removes_language_attribute(): void
    {
        $widget = TurnstileWidget::make()->language('es')->languageAuto();

        static::assertArrayNotHasKey('language', $widget->getDataAttributes());
    }

    public function test_language_app_uses_the_configured_application_locale(): void
    {
        $this->app['config']->set('app.locale', 'fr');

        $widget = TurnstileWidget::make()->languageApp();

        static::assertSame('fr', $widget->getDataAttributes()['language']);
    }

    public function test_language_app_reflects_runtime_locale_change(): void
    {
        $this->app['config']->set('app.locale', 'de');

        $widget = TurnstileWidget::make()->languageApp();

        static::assertSame('de', $widget->getDataAttributes()['language']);
    }

    /*
    |--------------------------------------------------------------------------
    | Miscellaneous
    |--------------------------------------------------------------------------
    */
    public function test_tabindex_sets_tabindex_attribute(): void
    {
        $widget = TurnstileWidget::make()->tabindex(3);

        static::assertSame(3, $widget->getDataAttributes()['tabindex']);
    }

    public function test_callback_sets_callback_attribute_to_given_function_name(): void
    {
        $widget = TurnstileWidget::make()->callback('myOnSuccess');

        static::assertSame('myOnSuccess', $widget->getDataAttributes()['callback']);
    }

    public function test_action_name_sets_action_attribute(): void
    {
        $widget = TurnstileWidget::make()->actionName('login');

        static::assertSame('login', $widget->getDataAttributes()['action']);
    }

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    */

    public function test_widget_is_explicit_by_default(): void
    {
        $widget = TurnstileWidget::make();

        static::assertTrue($widget->isExplicit());
        static::assertFalse($widget->isImplicit());
    }

    public function test_implicit_makes_widget_switch_to_implicit_mode(): void
    {
        $widget = TurnstileWidget::make()->implicit();

        static::assertFalse($widget->isExplicit());
        static::assertTrue($widget->isImplicit());
    }

    public function test_implicit_true_is_the_same_as_calling_implicit_without_args(): void
    {
        $widget = TurnstileWidget::make()->implicit(true);

        static::assertFalse($widget->isExplicit());
        static::assertTrue($widget->isImplicit());
    }

    public function test_implicit_false_reverts_widget_back_to_explicit_mode(): void
    {
        $widget = TurnstileWidget::make()->implicit()->implicit(false);

        static::assertTrue($widget->isExplicit());
        static::assertFalse($widget->isImplicit());
    }

    /*
    |--------------------------------------------------------------------------
    | Fluent methods
    |--------------------------------------------------------------------------
    */

    public function test_fluent_methods_return_the_same_instance(): void
    {
        $widget = TurnstileWidget::make();

        static::assertSame($widget, $widget->flexible());
        static::assertSame($widget, $widget->compact());
        static::assertSame($widget, $widget->normal());
        static::assertSame($widget, $widget->light());
        static::assertSame($widget, $widget->dark());
        static::assertSame($widget, $widget->system());
        static::assertSame($widget, $widget->appearanceExecute());
        static::assertSame($widget, $widget->appearanceInteractionOnly());
        static::assertSame($widget, $widget->appearanceAlways());
        static::assertSame($widget, $widget->executionExecute());
        static::assertSame($widget, $widget->executionRender());
        static::assertSame($widget, $widget->language('en'));
        static::assertSame($widget, $widget->languageAuto());
        static::assertSame($widget, $widget->tabindex(0));
        static::assertSame($widget, $widget->callback('fn'));
        static::assertSame($widget, $widget->actionName('act'));
        static::assertSame($widget, $widget->implicit());
        static::assertSame($widget, $widget->dataAttribute('key', 'val'));
    }

    // =========================================================================
    // Validation guard – isLiveFieldUpdate()
    //
    // The protected method is tested via reflection to keep assertions precise
    // and independent of what parent::getValidationRules() returns in each
    // Filament version. They often dance to their own tunes, not mine.
    // =========================================================================

    public function test_is_not_live_field_update_for_a_plain_http_request(): void
    {
        // No X-Livewire header → regular form POST → must NOT skip validation.
        $this->app->instance('request', Request::create('/submit', 'POST'));

        static::assertFalse($this->callIsLiveFieldUpdate(TurnstileWidget::make()));
    }

    public function test_is_live_field_update_when_livewire_request_carries_no_calls(): void
    {
        $this->simulateLivewireRequest();

        static::assertTrue($this->callIsLiveFieldUpdate(TurnstileWidget::make()));
    }

    public function test_is_live_field_update_when_livewire_request_carries_empty_calls_array(): void
    {
        // An explicit empty array is also a "no calls" payload.
        $this->simulateLivewireRequest(
            components: [['snapshot' => [], 'updates' => [], 'calls' => []]],
        );

        static::assertTrue($this->callIsLiveFieldUpdate(TurnstileWidget::make()));
    }

    public function test_is_not_live_field_update_when_livewire_request_has_a_method_call(): void
    {
        $this->simulateLivewireRequest(hasCalls: true);

        static::assertFalse($this->callIsLiveFieldUpdate(TurnstileWidget::make()));
    }

    public function test_is_not_live_field_update_when_one_of_several_components_has_calls(): void
    {
        // A multi-component payload where only one "component" carries a call
        // must still be treated as a form submission from the frontend.
        $this->simulateLivewireRequest(components: [
            ['snapshot' => [], 'updates' => [], 'calls' => []],
            ['snapshot' => [], 'updates' => [], 'calls' => [['method' => 'save', 'params' => []]]],
        ]);

        static::assertFalse($this->callIsLiveFieldUpdate(TurnstileWidget::make()));
    }

    public function test_is_live_field_update_when_all_components_have_no_calls(): void
    {
        $this->simulateLivewireRequest(components: [
            ['snapshot' => [], 'updates' => [], 'calls' => []],
            ['snapshot' => [], 'updates' => [], 'calls' => []],
        ]);

        static::assertTrue($this->callIsLiveFieldUpdate(TurnstileWidget::make()));
    }

    // =========================================================================
    // getValidationRules()
    // =========================================================================

    // The whole purpose of the guard is very simple: a property-sync request
    // must receive the required rule so the Turnstile validation rule never
    // calls the Cloudflare siteverify endpoint and the token is preserved.
    public function test_get_validation_rules_returns_default_array_for_live_field_update(): void
    {
        $this->simulateLivewireRequest(hasCalls: false);

        static::assertSame(['required'], $this->resolveWidget()->getValidationRules());
    }

    // A real form submission (Livewire request with calls) must pass through to parent rules.
    public function test_get_validation_rules_returns_non_empty_rules_for_livewire_form_submit(): void
    {
        $this->simulateLivewireRequest(hasCalls: true);

        static::assertNotEmpty($this->resolveWidget()->getValidationRules());
    }

    // A plain HTTP POST is never a live-field update and must always be validated.
    public function test_get_validation_rules_returns_non_empty_rules_for_plain_http_request(): void
    {
        $this->app->instance('request', Request::create('/submit', 'POST'));

        $rules = $this->resolveWidget()->getValidationRules();

        static::assertNotEmpty($rules);
    }

    /*
    |--------------------------------------------------------------------------
    | Blade – explicit mode (default)
    |--------------------------------------------------------------------------
    */

    // Explicit mode: Alpine targets a plain <div x-ref="widget"> for turnstile.render().
    public function test_explicit_mode_renders_x_ref_widget_div(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('x-ref="widget"');
    }

    // Explicit mode must NOT output a .cf-turnstile div (that is implicit mode).
    public function test_explicit_mode_does_not_render_cf_turnstile_class(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertDontSeeHtml('class="cf-turnstile"');
    }

    // Explicit mode calls _mountExplicit() from Alpine's init().
    public function test_explicit_mode_calls_mount_explicit_in_init(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('_mountExplicit');
    }

    // The sitekey must appear as a JS render option in explicit mode.
    public function test_explicit_mode_emits_sitekey_as_js_render_option(): void
    {
        $siteKey = app(Turnstile::class)->getSiteKey();

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml($siteKey);
    }

    // When no user callback is configured, the Blade renders the default
    // Alpine-only callback that stores the token in local state.
    public function test_explicit_mode_renders_default_alpine_callback_when_no_user_callback_is_set(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('callback: (t) => { this.token = t; }');
    }

    // When a user callback IS configured, the default short form must NOT be
    // emitted; instead the chained version (which still sets this.token) is used.
    public function test_explicit_mode_does_not_render_default_callback_when_user_callback_is_set(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->callback('myOnSuccess'),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertDontSeeHtml('callback: (t) => { this.token = t; }');
    }

    // When a user callback is set, the chained handler must still assign
    // this.token and also invoke the user-supplied function.
    public function test_explicit_mode_chained_callback_assigns_token_and_calls_user_function(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->callback('myOnSuccess'),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('this.token = t')
            ->assertSeeHtml('myOnSuccess');
    }

    // A custom field name must surface as the 'response-field-name' JS render
    // option (Cloudflare's documented param) rather than as a 'field-name' key.
    public function test_explicit_mode_maps_field_name_to_response_field_name_js_option(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->name('my-captcha'),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml("'response-field-name'")
            ->assertSeeHtml('my-captcha')
            ->assertDontSeeHtml('data-field-name');
    }

    // 'field-name' must never appear as a raw JS key in explicit mode.
    public function test_explicit_mode_does_not_emit_field_name_as_raw_js_key(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->name('my-captcha'),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertDontSeeHtml('"field-name"');
    }

    public function test_explicit_mode_emits_flexible_size_as_js_render_option(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->flexible(),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml("'size': 'flexible'");
    }

    public function test_explicit_mode_emits_dark_theme_as_js_render_option(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->dark(),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml("'theme': 'dark'");
    }

    public function test_explicit_mode_emits_language_as_js_render_option(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->language('ja'),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml("'language': 'ja'");
    }

    public function test_explicit_mode_emits_action_as_js_render_option(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->actionName('register'),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml("'action': 'register'");
    }

    public function test_explicit_mode_emits_tabindex_as_js_render_option(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->tabindex(2),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml("'tabindex': 2");
    }

    public function test_explicit_mode_emits_expired_and_error_callbacks(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('expired-callback')
            ->assertSeeHtml('error-callback');
    }

    /*
    |--------------------------------------------------------------------------
    | Blade – implicit mode
    |--------------------------------------------------------------------------
    */

    // Implicit mode: Cloudflare auto-discovers the .cf-turnstile div by class.
    public function test_implicit_mode_renders_cf_turnstile_class_on_target_div(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit(),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('class="cf-turnstile"');
    }

    // Implicit mode must NOT render the x-ref="widget" div used by turnstile.render().
    public function test_implicit_mode_does_not_render_x_ref_widget_div(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit(),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertDontSeeHtml('x-ref="widget"');
    }

    // Implicit mode calls _mountImplicit() from Alpine's init().
    public function test_implicit_mode_calls_mount_implicit_in_init(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit(),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('_mountImplicit');
    }

    // Implicit mode registers globally-named callbacks so Cloudflare's script
    // can call them on the correct widget instance even when multiple widgets
    // exist on the same page.
    public function test_implicit_mode_registers_uniquely_named_global_token_callback(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit(),
        ];

        $html = Livewire::test(TurnstileWidgetPage::class)->html();

        static::assertMatchesRegularExpression(
            '/data-callback="_turnstileToken_[^"]+"/',
            $html,
        );
    }

    public function test_implicit_mode_registers_uniquely_named_global_expired_callback(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit(),
        ];

        $html = Livewire::test(TurnstileWidgetPage::class)->html();

        static::assertMatchesRegularExpression(
            '/data-expired-callback="_turnstileExpired_[^"]+"/',
            $html,
        );
    }

    public function test_implicit_mode_registers_uniquely_named_global_error_callback(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit(),
        ];

        $html = Livewire::test(TurnstileWidgetPage::class)->html();

        static::assertMatchesRegularExpression(
            '/data-error-callback="_turnstileError_[^"]+"/',
            $html,
        );
    }

    // Implicit mode emits the sitekey as a data-* attribute on the widget div.
    public function test_implicit_mode_renders_sitekey_as_data_attribute(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit(),
        ];

        $siteKey = app(Turnstile::class)->getSiteKey();

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml("data-sitekey=\"{$siteKey}\"");
    }

    public function test_implicit_mode_renders_flexible_size_as_data_attribute(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit()->flexible(),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('data-size="flexible"');
    }

    public function test_implicit_mode_renders_dark_theme_as_data_attribute(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit()->dark(),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('data-theme="dark"');
    }

    public function test_implicit_mode_renders_language_as_data_attribute(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit()->language('ko'),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('data-language="ko"');
    }

    // A custom field name must surface as data-response-field-name (the
    // Cloudflare-documented attribute) rather than as data-field-name.
    public function test_implicit_mode_maps_field_name_to_data_response_field_name_attribute(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit()->name('my-captcha'),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('data-response-field-name="my-captcha"')
            ->assertDontSeeHtml('data-field-name');
    }

    // The widget div must carry a unique id="turnstile-{id}" for targeting.
    public function test_implicit_mode_widget_div_has_unique_id(): void
    {
        $widget = null;

        TurnstileWidgetPage::$form = function() use (&$widget) {
            return [
                $widget = TurnstileWidget::make()->implicit(),
            ];
        };

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml("id=\"turnstile-{$widget->getId()}\"");
    }

    // Implicit mode must include cleanup (delete) of the global callbacks in
    // Alpine's destroy() hook to avoid memory leaks on SPA navigation.
    public function test_implicit_mode_cleans_up_global_callbacks_in_destroy(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit(),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('delete window[');
    }

    // Explicit mode must NOT emit the global callback cleanup (no globals to delete).
    public function test_explicit_mode_does_not_emit_global_callback_cleanup(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertDontSeeHtml('delete window[');
    }

    /*
    |--------------------------------------------------------------------------
    | Blade – hidden input (both modes)
    |--------------------------------------------------------------------------
    */

    // The hidden input name must default to the canonical key so classic POST forms work.
    public function test_hidden_input_defaults_to_canonical_key_as_name(): void
    {
        $key = app(Turnstile::class)->key();

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml("name=\"{$key}\"");
    }

    // When the field is renamed, the hidden input must reflect the custom name.
    public function test_hidden_input_uses_custom_name_when_field_is_renamed(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->name('my-captcha'),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('name="my-captcha"');
    }

    // The hidden input must carry an Alpine :value binding to the local token variable.
    public function test_hidden_input_has_alpine_value_binding_to_token(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml(':value="token"');
    }

    public function test_hidden_input_type_is_hidden(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('type="hidden"');
    }

    // The custom name applies equally to the hidden input in implicit mode.
    public function test_hidden_input_uses_custom_name_in_implicit_mode(): void
    {
        TurnstileWidgetPage::$form = fn() => [
            TurnstileWidget::make()->implicit()->name('my-captcha'),
        ];

        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('name="my-captcha"');
    }

    /*
    |--------------------------------------------------------------------------
    | Blade – Alpine / Livewire wiring
    |--------------------------------------------------------------------------
    */

    // wire:ignore prevents Livewire from diffing and patching the widget DOM
    // on subsequent renders, which would break the embedded Cloudflare iframe.
    public function test_container_has_wire_ignore_directive(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('wire:ignore');
    }

    // The commit hook must use commit.updates[path] to inject the token.
    public function test_commit_hook_injects_token_via_updates_path(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('commit.updates[path]');
    }

    // The commit hook must guard on commit.calls.length to detect form submits.
    public function test_commit_hook_guards_on_calls_length(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('commit.calls');
    }

    // The DOM and window reset listeners must both be registered.
    public function test_widget_registers_dom_and_window_reset_event_listeners(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('turnstile:reset')
            ->assertSeeHtml('reset-turnstile');
    }

    // The Livewire hook must be removed in destroy() to avoid memory leaks.
    public function test_destroy_hook_removes_livewire_commit_listener(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('_removeHook');
    }

    // Alpine's local token state must be initialized to null (not entangled).
    public function test_alpine_token_state_is_initialised_to_null(): void
    {
        Livewire::test(TurnstileWidgetPage::class)
            ->assertSeeHtml('token:       null');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    // Mount a TurnstileWidget in the Filament/Livewire context so configure()
    // and all dependency-injected evaluate() calls have run by the time the
    // widget instance is returned to the caller.
    //
    // An optional Closure receives the fresh widget instance before it is
    // returned to the test, allowing additional fluent configuration.
    protected function resolveWidget(Closure $configure = null): TurnstileWidget
    {
        $widget = null;

        TurnstileWidgetPage::$form = function () use (&$widget, $configure) {
            $widget = TurnstileWidget::make();
            if ($configure !== null) {
                $configure($widget);
            }
            return [$widget];
        };

        Livewire::test(TurnstileWidgetPage::class);

        return $widget;
    }

    // Invoke the protected TurnstileWidget::isLiveFieldUpdate() method via reflection and return its boolean result.
    protected function callIsLiveFieldUpdate(TurnstileWidget $widget): bool
    {
        return (new ReflectionMethod($widget, 'isLiveFieldUpdate'))->invoke($widget);
    }

    // Replace the active request with a fake Livewire AJAX commit request.
    protected function simulateLivewireRequest(bool $hasCalls = false, ?array $components = null): void
    {
        $components ??= [
            [
                'snapshot' => [],
                'updates' => [],
                'calls' => $hasCalls
                    ? [['method' => 'save', 'params' => []]]
                    : [],
            ],
        ];

        $request = Request::create(
            '/livewire/update',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_X_LIVEWIRE' => '1',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['components' => $components]),
        );

        $this->app->instance('request', $request);
    }
}

if (class_exists(FilamentServiceProvider::class)) {
    class TurnstileWidgetPage extends Page
    {
        public static Closure $form;

        protected string $view = 'filament-panels::pages.simple';

        public array $data = [];

        public function hasLogo()
        {
            return false;
        }

        public function content(Schema $schema): Schema
        {
            return $schema->components((static::$form)())->statePath('data');
        }
    }
}
