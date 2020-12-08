<?php
/**
 * @license see LICENSE
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
