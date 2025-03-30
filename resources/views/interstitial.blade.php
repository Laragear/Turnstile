<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('turnstile::interstitial.title') }}</title>
    <script>
        window.submitForm = () => document.getElementById("form").submit()
    </script>
    <style>
        /*! tailwindcss v4.0.17 | MIT License | https://tailwindcss.com */
        @layer theme, base, components, utilities;
        @layer theme {
            :root, :host {
                --font-sans: ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
                'Noto Color Emoji';
                --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New',
                monospace;
                --color-orange-500: oklch(0.705 0.213 47.604);
                --color-slate-800: oklch(0.279 0.041 260.031);
                --color-slate-950: oklch(0.129 0.042 264.695);
                --color-gray-500: oklch(0.551 0.027 264.364);
                --color-white: #fff;
                --spacing: 0.25rem;
                --container-md: 28rem;
                --container-2xl: 42rem;
                --text-sm: 0.875rem;
                --text-sm--line-height: calc(1.25 / 0.875);
                --font-weight-semibold: 600;
                --tracking-wide: 0.025em;
                --radius-xl: 0.75rem;
                --default-font-family: var(--font-sans);
                --default-mono-font-family: var(--font-mono);
            }
        }
        @layer base {
            *, ::after, ::before, ::backdrop, ::file-selector-button {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
                border: 0 solid;
            }
            html, :host {
                line-height: 1.5;
                -webkit-text-size-adjust: 100%;
                tab-size: 4;
                font-family: var(--default-font-family, ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji');
                font-feature-settings: var(--default-font-feature-settings, normal);
                font-variation-settings: var(--default-font-variation-settings, normal);
                -webkit-tap-highlight-color: transparent;
            }
            hr {
                height: 0;
                color: inherit;
                border-top-width: 1px;
            }
            abbr:where([title]) {
                -webkit-text-decoration: underline dotted;
                text-decoration: underline dotted;
            }
            h1, h2, h3, h4, h5, h6 {
                font-size: inherit;
                font-weight: inherit;
            }
            a {
                color: inherit;
                -webkit-text-decoration: inherit;
                text-decoration: inherit;
            }
            b, strong {
                font-weight: bolder;
            }
            code, kbd, samp, pre {
                font-family: var(--default-mono-font-family, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace);
                font-feature-settings: var(--default-mono-font-feature-settings, normal);
                font-variation-settings: var(--default-mono-font-variation-settings, normal);
                font-size: 1em;
            }
            small {
                font-size: 80%;
            }
            sub, sup {
                font-size: 75%;
                line-height: 0;
                position: relative;
                vertical-align: baseline;
            }
            sub {
                bottom: -0.25em;
            }
            sup {
                top: -0.5em;
            }
            table {
                text-indent: 0;
                border-color: inherit;
                border-collapse: collapse;
            }
            :-moz-focusring {
                outline: auto;
            }
            progress {
                vertical-align: baseline;
            }
            summary {
                display: list-item;
            }
            ol, ul, menu {
                list-style: none;
            }
            img, svg, video, canvas, audio, iframe, embed, object {
                display: block;
                vertical-align: middle;
            }
            img, video {
                max-width: 100%;
                height: auto;
            }
            button, input, select, optgroup, textarea, ::file-selector-button {
                font: inherit;
                font-feature-settings: inherit;
                font-variation-settings: inherit;
                letter-spacing: inherit;
                color: inherit;
                border-radius: 0;
                background-color: transparent;
                opacity: 1;
            }
            :where(select:is([multiple], [size])) optgroup {
                font-weight: bolder;
            }
            :where(select:is([multiple], [size])) optgroup option {
                padding-inline-start: 20px;
            }
            ::file-selector-button {
                margin-inline-end: 4px;
            }
            ::placeholder {
                opacity: 1;
            }
            @supports (not (-webkit-appearance: -apple-pay-button)) or (contain-intrinsic-size: 1px) {
                ::placeholder {
                    color: color-mix(in oklab, currentColor 50%, transparent);
                }
            }
            textarea {
                resize: vertical;
            }
            ::-webkit-search-decoration {
                -webkit-appearance: none;
            }
            ::-webkit-date-and-time-value {
                min-height: 1lh;
                text-align: inherit;
            }
            ::-webkit-datetime-edit {
                display: inline-flex;
            }
            ::-webkit-datetime-edit-fields-wrapper {
                padding: 0;
            }
            ::-webkit-datetime-edit, ::-webkit-datetime-edit-year-field, ::-webkit-datetime-edit-month-field, ::-webkit-datetime-edit-day-field, ::-webkit-datetime-edit-hour-field, ::-webkit-datetime-edit-minute-field, ::-webkit-datetime-edit-second-field, ::-webkit-datetime-edit-millisecond-field, ::-webkit-datetime-edit-meridiem-field {
                padding-block: 0;
            }
            :-moz-ui-invalid {
                box-shadow: none;
            }
            button, input:where([type='button'], [type='reset'], [type='submit']), ::file-selector-button {
                appearance: button;
            }
            ::-webkit-inner-spin-button, ::-webkit-outer-spin-button {
                height: auto;
            }
            [hidden]:where(:not([hidden='until-found'])) {
                display: none!important;
            }
        }
        @layer utilities {
            .mx-auto {
                margin-inline: auto;
            }
            .mt-2 {
                margin-top: calc(var(--spacing) * 2);
            }
            .flex {
                display: flex;
            }
            .h-screen {
                height: 100vh;
            }
            .min-h-\[75px\] {
                min-height: 75px;
            }
            .w-screen {
                width: 100vw;
            }
            .max-w-md {
                max-width: var(--container-md);
            }
            .items-center {
                align-items: center;
            }
            .justify-center {
                justify-content: center;
            }
            .space-y-8 {
                :where(& > :not(:last-child)) {
                    --tw-space-y-reverse: 0;
                    margin-block-start: calc(calc(var(--spacing) * 8) * var(--tw-space-y-reverse));
                    margin-block-end: calc(calc(var(--spacing) * 8) * calc(1 - var(--tw-space-y-reverse)));
                }
            }
            .overflow-hidden {
                overflow: hidden;
            }
            .rounded-xl {
                border-radius: var(--radius-xl);
            }
            .bg-white {
                background-color: var(--color-white);
            }
            .p-8 {
                padding: calc(var(--spacing) * 8);
            }
            .text-center {
                text-align: center;
            }
            .text-sm {
                font-size: var(--text-sm);
                line-height: var(--tw-leading, var(--text-sm--line-height));
            }
            .font-semibold {
                --tw-font-weight: var(--font-weight-semibold);
                font-weight: var(--font-weight-semibold);
            }
            .tracking-wide {
                --tw-tracking: var(--tracking-wide);
                letter-spacing: var(--tracking-wide);
            }
            .text-gray-500 {
                color: var(--color-gray-500);
            }
            .text-orange-500 {
                color: var(--color-orange-500);
            }
            .uppercase {
                text-transform: uppercase;
            }
            .shadow-md {
                --tw-shadow: 0 4px 6px -1px var(--tw-shadow-color, rgb(0 0 0 / 0.1)), 0 2px 4px -2px var(--tw-shadow-color, rgb(0 0 0 / 0.1));
                box-shadow: var(--tw-inset-shadow), var(--tw-inset-ring-shadow), var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow);
            }
            .md\:flex {
                @media (width >= 48rem) {
                    display: flex;
                }
            }
            .md\:max-w-2xl {
                @media (width >= 48rem) {
                    max-width: var(--container-2xl);
                }
            }
            .dark\:bg-slate-800 {
                @media (prefers-color-scheme: dark) {
                    background-color: var(--color-slate-800);
                }
            }
            .dark\:bg-slate-950 {
                @media (prefers-color-scheme: dark) {
                    background-color: var(--color-slate-950);
                }
            }
        }
        @property --tw-space-y-reverse {
            syntax: "*";
            inherits: false;
            initial-value: 0;
        }
        @property --tw-font-weight {
            syntax: "*";
            inherits: false;
        }
        @property --tw-tracking {
            syntax: "*";
            inherits: false;
        }
        @property --tw-shadow {
            syntax: "*";
            inherits: false;
            initial-value: 0 0 #0000;
        }
        @property --tw-shadow-color {
            syntax: "*";
            inherits: false;
        }
        @property --tw-inset-shadow {
            syntax: "*";
            inherits: false;
            initial-value: 0 0 #0000;
        }
        @property --tw-inset-shadow-color {
            syntax: "*";
            inherits: false;
        }
        @property --tw-ring-color {
            syntax: "*";
            inherits: false;
        }
        @property --tw-ring-shadow {
            syntax: "*";
            inherits: false;
            initial-value: 0 0 #0000;
        }
        @property --tw-inset-ring-color {
            syntax: "*";
            inherits: false;
        }
        @property --tw-inset-ring-shadow {
            syntax: "*";
            inherits: false;
            initial-value: 0 0 #0000;
        }
        @property --tw-ring-inset {
            syntax: "*";
            inherits: false;
        }
        @property --tw-ring-offset-width {
            syntax: "<length>";
            inherits: false;
            initial-value: 0px;
        }
        @property --tw-ring-offset-color {
            syntax: "*";
            inherits: false;
            initial-value: #fff;
        }
        @property --tw-ring-offset-shadow {
            syntax: "*";
            inherits: false;
            initial-value: 0 0 #0000;
        }
    </style>
    <x-turnstile::script />
</head>
<body>
<div class="flex h-screen w-screen items-center justify-center p-8 bg-slate-100 dark:bg-slate-950">
    <div class="mx-auto max-w-md overflow-hidden rounded-xl shadow-md md:max-w-2xl bg-slate-50 dark:bg-slate-800">
        <div class="md:flex">
            <div class="p-8 space-y-8 text-center">
                <div class="text-sm font-semibold tracking-wide text-orange-500 uppercase">
                    {{ __('turnstile::interstitial.title') }}
                </div>
                <form id="form" method="post" class="min-h-[75px]">
                    <div class="flex justify-center">
                        <x-turnstile::widget data-action="interstitial" data-callback="submitForm" />
                    </div>
                    @error(\Laragear\Turnstile\Turnstile::KEY)
                    <div class="text-red-800 dark:text-red-500 text-sm pt-2">
                        The challenge is invalid. Try again or
                        <a href="javascript:location.reload()" class="underline">refresh the page</a>.
                    </div>
                    @enderror
                </form>
                <p class="mt-2 text-gray-500 text-xm">
                    {{ __('turnstile::interstitial.description') }}
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
