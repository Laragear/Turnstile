<?php

namespace Laragear\Turnstile\Views\Components;

use Closure;
use Illuminate\View\Component;
use Laragear\Turnstile\Turnstile;

class Widget extends Component
{
    /**
     * Create a new Blade Component instance.
     */
    public function __construct(protected Turnstile $turnstile)
    {
        // ...
    }

    /**
     * Get the view / view contents that represent the component.
     *
     * @return (\Closure():string)|string
     */
    public function render(): Closure|string
    {
        if ($this->turnstile->isDisabled()) {
            return '';
        }

        return function (): string {
            $siteKey = $this->turnstile->getSiteKey();

            return "<div {{ \$attributes->merge(['class' => 'cf-turnstile', 'data-sitekey' => '$siteKey']) }}></div>";
        };
    }
}
