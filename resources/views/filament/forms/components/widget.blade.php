{{--
    Cloudflare Turnstile widget for Filament v5
    ============================================

    Token-lifecycle strategy
    ------------------------
    Cloudflare Turnstile tokens are single-use. Filament forms can contain
    fields that use ->live() / ->live('blur'), which causes Livewire to fire
    intermediate AJAX requests (property-sync commits) to validate those fields.

    Without special handling the token would be forwarded to the server during
    every such intermediate request, causing the Cloudflare siteverify endpoint
    to consume it before the form is finally submitted.

    This view uses a two-layer defense:

    CLIENT LAYER: the token is held exclusively in a local Alpine variable
    (never entangled with Livewire state). A Livewire 'commit' hook inspects
    every outgoing request; the token is injected into commit.updates *only*
    when the commit contains at least one method call, like a real Livewire
    action (form submit). Pure property-sync commits (live-field updates)
    leave the token out entirely.

    SERVER LAYER: TurnstileWidget::getValidationRules() detects live-field
    update requests (X-Livewire header + no calls in any component payload) and
    returns an empty rule set for the Turnstile field, so even if the token were
    somehow present in the form, it would not be validated nor consumed.

    Rendering modes
    ---------------
    Explicit (default, $isExplicit() === true):
        turnstile.render() is called from Alpine. All widget options are passed
        as a plain JS object – no data-* attributes on the DOM element.

    Implicit ($implicit(true) on the PHP class):
        The Turnstile script auto-discovers the .cf-turnstile div by class.
        Named global callback functions are registered so Alpine can still
        capture the token without blocking the auto-discovery flow.

--}}
@php
    $isExplicitMode = $isExplicit();
    $fieldId = $getId();
    $statePath = $getStatePath();
    $dataAttrs = $getDataAttributes();
    $hasCustomCallback = isset($dataAttrs['callback']);
    $customCallbackName = $dataAttrs['callback'] ?? null;

    $renderOptions = [];
    foreach ($dataAttrs as $key => $value) {
        if ($key === 'field-name') {
            $renderOptions['response-field-name'] = $value;
        } elseif ($key !== 'callback') {
            $renderOptions[$key] = $value;
        }
    }
@endphp
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            token:       $wire.entangle(@js($statePath)),
            widgetId:    null,
            _removeHook: null,

            /**
             * Lifecycle
             */
            init() {
                {{ $isExplicitMode ? 'this._mountExplicit();' : 'this._mountImplicit();' }}

                // Allow external code (like after a failed submission) to reset
                // the widget via a custom DOM event or a global window event.
                this.$el.addEventListener('turnstile:reset', () => this._reset());
                window.addEventListener('reset-turnstile',  () => this._reset());

                // Scope the commit hook to our Livewire component only.
                const wireEl = this.$el.closest('[wire\\:id]');
                const wireId = wireEl ? wireEl.getAttribute('wire:id') : null;
                const path   = @js($statePath);

                if (wireId && window.Livewire) {
                    this._removeHook = Livewire.hook('commit', ({ component, commit }) => {
                        // Ignore commits from other Livewire components on the page.
                        if (component.id !== wireId) return;

                        // --------------------------------------------------------
                        // CLIENT-LAYER GUARD
                        // Only inject the token when this commit is carrying at
                        // least one method call (a real Livewire action / form
                        // submit). Pure property-sync commits (live-field updates
                        // triggered by ->live()) have an empty calls array and must
                        // not receive the token so it is not consumed prematurely.
                        // --------------------------------------------------------
                        const isFormSubmit =
                            Array.isArray(commit.calls) && commit.calls.length > 0;

                        if (isFormSubmit && this.token !== null) {
                            commit.updates[path] = this.token;
                        }
                    });
                }
            },

            destroy() {
                // Unregister the Livewire hook to avoid memory leaks when the
                // component is removed from the DOM (e.g. after navigation).
                if (typeof this._removeHook === 'function') {
                    this._removeHook();
                }

                {{ $isExplicitMode ? '' : "delete window['_turnstileToken_{$fieldId}'];
                    delete window['_turnstileExpired_{$fieldId}'];
                    delete window['_turnstileError_{$fieldId}'];" }}
            },

            _mountExplicit() {
                // 1. Instant check: If it's already here, just run.
                if (window.turnstile) {
                    return this._renderExplicit();
                }

                // 2. Identify the script tag
                const script = document.querySelector(`script[src*='turnstile/v0/api.js']`);

                // If the script tag exists but turnstile isn't ready, listen for the load event.
                if (script) {
                    script.addEventListener('load', () => this._renderExplicit(), { once: true });
                } else
                // 3. Fallback: If the script isn't even in the DOM yet, observe the <head>
                {
                    const observer = new MutationObserver((mutations, obs) => {
                        const addedScript = document.querySelector(`script[src*='turnstile/v0/api.js']`);

                        if (addedScript) {
                            addedScript.addEventListener('load', () => this._renderExplicit(), { once: true });
                            obs.disconnect(); // Stop watching once we find it
                        }
                    });

                    observer.observe(document.head, { childList: true });
                }
            },

            _renderExplicit() {
                    {{-- --------------------------------------------------------
                         Forward all PHP-side data attributes as render options,
                         translating the internal 'field-name' key to Cloudflare's
                         'response-field-name' parameter so the hidden input that
                         Turnstile injects uses the correct name on non-Livewire
                         (traditional POST) form submissions as well.
                    --------------------------------------------------------- --}}
                const opts = @js($renderOptions);
                const customCb = {{ $hasCustomCallback ? '@js($customCallbackName)' : 'null' }};

                opts.callback = (t) => {
                    this.token = t;
                    if (customCb && typeof window[customCb] === 'function') {
                        window[customCb](t);
                    }
                };
                opts['expired-callback'] = () => { this.token = null; this._reset(); };
                opts['error-callback']   = () => { this.token = null; };

                this.widgetId = turnstile.render(this.$refs.widget, opts);
            },

            /**
             * Implicit rendering  (opt-in via ->implicit() on the PHP field)
             */
            _mountImplicit() {
                // Register uniquely-named global functions that the Turnstile
                // script can call once it has auto-discovered the .cf-turnstile
                // div. Using the field's unique ID avoids collisions when
                // multiple Turnstile widgets appear on the same page.
                window['_turnstileToken_{{ $fieldId }}']   = (t)  => { this.token = t;    };
                window['_turnstileExpired_{{ $fieldId }}']  = ()   => { this.token = null; this._reset(); };
                window['_turnstileError_{{ $fieldId }}']    = ()   => { this.token = null; };
            },

            // -----------------------------------------------------------------
            // Reset helper
            // -----------------------------------------------------------------

            _reset() {
                this.token = null;
                if (this.widgetId !== null && window.turnstile) {
                    turnstile.reset(this.widgetId);
                }
            },
        }"
        wire:ignore
    >

        {{--
            ----------------------------------------------------------------
            Explicit rendering target
                The turnstile.render() call in _renderExplicit() injects the
                Cloudflare iframe directly into this element.
            -----------------------------------------------------------------
        --}}
        @if ($isExplicit())
            <div x-ref="widget"></div>

            {{--
                ----------------------------------------------------------------
                Implicit rendering target
                    The Turnstile script auto-discovers this div via the cf-turnstile
                    class. Named callbacks registered above feed tokens back into the
                    Alpine state. The 'field-name' data attribute is mapped to the
                    proper data-response-field-name attribute Cloudflare expects.
                -----------------------------------------------------------------
            --}}
        @else
            <x-turnstile::widget :attributes="$getExpandedAttributesBag()"/>
        @endif

        {{--
            Hidden input – fallback for non-Livewire (classic HTML POST) usage.
            For Livewire submissions the token is injected via the commit hook
            above, so this input is transparent to the Livewire data flow.

            The name mirrors the field's configured key so both submission paths
            deliver the token under the same backend key:
              - Livewire  → commit.updates[$getStatePath()]  = token
              - HTML POST → $_POST[$getName()]               = token
        --}}
        <input type="hidden" name="{{ $getName() }}" :value="token" x-ref="hiddenToken">
    </div>

    {{--
        Turnstile Script - injects the Turnstile script from Cloudflare into
        the "scripts" stack so the widget can process both the challenge and
        the render, ensuring it's only pushed once in the view.
    --}}
    @if($getScript())
        @pushonce($getScriptStack())
            <x-turnstile::script :attributes="$getScriptAttributes()"/>
        @endpushonce
    @endif
</x-dynamic-component>
