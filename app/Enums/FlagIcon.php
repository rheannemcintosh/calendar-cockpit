<?php

namespace App\Enums;

/**
 * The icons a flag can show, each the kebab-case name of a Lucide icon.
 */
enum FlagIcon: string
{
    case PoundSterling = 'pound-sterling';
    case Lock = 'lock';
    case Star = 'star';
}
