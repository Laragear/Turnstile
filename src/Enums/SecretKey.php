<?php

namespace Laragear\Turnstile\Enums;

enum SecretKey: string
{
    case Passing = '1x0000000000000000000000000000000AA';
    case Fails = '2x0000000000000000000000000000000AA';
    case Spent = '3x0000000000000000000000000000000AA';
}
