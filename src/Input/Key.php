<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Input;

/**
 * Holds key constants and their respective bit values.
 *
 * @see https://chromedevtools.github.io/devtools-protocol/1-2/Input/
 */
abstract class Key
{
    public const ALT = 1;
    public const CONTROL = 2;
    public const META = 4;
    public const SHIFT = 8;
    public const COMMAND = self::META;
}
