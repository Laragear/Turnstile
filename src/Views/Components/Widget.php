<?php

namespace Laragear\Turnstile\Views\Components;

use Closure;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Laragear\Turnstile\Enums\SiteKey;
use Laragear\Turnstile\Turnstile;

class Widget extends Component
{
    /**
     * Create a new Blade Component instance.
     */
    public function __construct(protected Turnstile $turnstile, public ?string $siteKey = null)
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
            $siteKey = match(Str::studly($this->siteKey)) {
                'VisiblePassing' => SiteKey::VisiblePassing->value,
                'VisibleBlocks' => SiteKey::VisibleBlocks->value,
                'InvisiblePassing' => SiteKey::InvisiblePassing->value,
                'InvisibleBlocks' => SiteKey::InvisibleBlocks->value,
                'ForceInteraction' => SiteKey::ForceInteraction->value,
                default => $this->turnstile->getSiteKey()
            };

            return "<div {{ \$attributes->merge(['class' => 'cf-turnstile', 'data-sitekey' => '$siteKey']) }}></div>";
        };
    }
}
