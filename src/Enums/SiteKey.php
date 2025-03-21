<?php

namespace Laragear\Turnstile\Enums;

enum SiteKey: string
{
    case VisiblePassing = '1x00000000000000000000AA';
    case VisibleBlocks = '2x00000000000000000000AB';
    case InvisiblePassing = '1x00000000000000000000BB';
    case InvisibleBlocks = '2x00000000000000000000BB';
    case ForceInteraction = '3x00000000000000000000FF';
}
