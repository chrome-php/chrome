<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Exception;

class OperationTimedOut extends \Exception
{
    public static function createFromTimeout(int $timeoutMicroSec): self
    {
        return new self('Operation timed out ('.self::getTimeoutPhrase($timeoutMicroSec).')');
    }

    private static function getTimeoutPhrase(int $timeoutMicroSec): string
    {
        if ($timeoutMicroSec > 1000 * 1000) {
            return (int) ($timeoutMicroSec / (1000 * 1000)).'sec';
        }
        if ($timeoutMicroSec > 1000) {
            return (int) ($timeoutMicroSec / 1000).'ms';
        }

        return (int) ($timeoutMicroSec).'μs';
    }
}
