<?php

namespace Laragear\Turnstile\Views\Components;

use Closure;
use Illuminate\View\Component;
use Laragear\Turnstile\Turnstile;
use function http_build_query;

class Script extends Component
{
    /**
     * The location of the Cloudflare Turnstile script.
     *
     * @const string
     */
    public const SOURCE = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

    /**
     * Create a new Blade Component instance.
     */
    public function __construct(
        protected Turnstile $turnstile,
        public bool $explicit = false,
        public ?string $onload = null,
        public bool $async = true,
        public bool $defer = true,
    )
    {
        //
    }

    /**
     * Get the view / view contents that represent the component.
     *
     * @return (\Closure():string)|string
     */
    public function render(): Closure|string
    {
        if ($this->turnstile->isDisabled()) {
            return fn() => '';
        }

        return function (): string {
            $source = static::SOURCE . $this->query();

            return <<<HTML
<script src="$source" {{ \$attributes }} {{ \implode(' ', \array_filter([\$defer ? 'defer' : '', \$async ? 'async' : '' ])) }}></script>
HTML
                ;
        };
    }

    /**
     * Generate the query parameters if the script has been set with custom attributes.
     */
    protected function query(): string
    {
        $query = [];

        if ($this->explicit) {
            $query['render'] = 'explicit';
        }

        if ($this->onload) {
            $query['onload'] = $this->onload;
        }

        return $query ? '?'.http_build_query($query) : '';
    }
}
