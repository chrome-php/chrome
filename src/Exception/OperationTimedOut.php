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
        return new self(\sprintf('Operation timed out after %s.', self::getTimeoutPhrase($timeoutMicroSec)));
    }

    private static function getTimeoutPhrase(int $timeoutMicroSec): string
    {
        if ($timeoutMicroSec > 1000 * 1000) {
            return \sprintf('%ds', (int) ($timeoutMicroSec / (1000 * 1000)));
        }

        if ($timeoutMicroSec > 1000) {
            return \sprintf('%dms', (int) ($timeoutMicroSec / 1000));
        }

        return \sprintf('%dμs', (int) ($timeoutMicroSec));
    }
}
