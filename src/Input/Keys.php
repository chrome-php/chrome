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
 * List of constants for common keyboard keys.
 *
 * Useful to find valid special keys and how they are written and capitalized
 */
abstract class Keys
{
    public const Escape = 'Escape';
    public const Tab = 'Tab';
    public const CapsLock = 'CapsLock';
    public const Shift = 'Shift';
    public const Control = 'Control';
    public const Meta = 'Meta';
    public const Command = 'Meta';
    public const Alt = 'Alt';
    public const Backspace = 'Backspace';
    public const Enter = 'Enter';
    public const Insert = 'Insert';
    public const Delete = 'Delete';
    public const Home = 'Home';
    public const End = 'End';
    public const PageUp = 'PageUp';
    public const PageDown = 'PageDown';
    public const Pause = 'Pause';
    public const NumLock = 'NumLock';
    public const ArrowUp = 'ArrowUp';
    public const ArrowDown = 'ArrowDown';
    public const ArrowRight = 'ArrowRight';
    public const ArrowLeft = 'ArrowLeft';
}
