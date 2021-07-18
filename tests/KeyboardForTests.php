<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\Input\KeyboardKeys;

class KeyboardForTests
{
    use KeyboardKeys {
        onKeyPress            as public;
        onKeyRelease          as public;
        toggleModifierFromKey as public;
        toggleModifier        as public;
        isKeyPressed          as public;
        setCurrentKey         as public;
    }
}
