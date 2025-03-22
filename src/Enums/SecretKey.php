<?php

namespace Laragear\Turnstile\Enums;

use Illuminate\Support\Collection;

enum SecretKey: string
{
    case Passing = '1x0000000000000000000000000000000AA';
    case Fails = '2x0000000000000000000000000000000AA';
    case Spent = '3x0000000000000000000000000000000AA';

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
