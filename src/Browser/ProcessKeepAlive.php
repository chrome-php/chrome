<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Browser;

use Symfony\Component\Process\Process;

class ProcessKeepAlive extends Process
{
    public function __destruct()
    {
        // Do nothing because we are in mode keep alive, default behavior is to kill the process
    }
}
