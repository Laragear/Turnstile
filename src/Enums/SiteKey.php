<?php

namespace Laragear\Turnstile\Enums;

use Illuminate\Support\Collection;

enum SiteKey: string
{
    case VisiblePassing = '1x00000000000000000000AA';
    case VisibleBlocks = '2x00000000000000000000AB';
    case InvisiblePassing = '1x00000000000000000000BB';
    case InvisibleBlocks = '2x00000000000000000000BB';
    case ForceInteraction = '3x00000000000000000000FF';

    /**
     * Returns the enum cases as a Collection.
     *
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function collect(): Collection
    {
        return new Collection(self::cases());
    }
}
