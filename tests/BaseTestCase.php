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

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    protected static function sitePath(string $file): string
    {
        $path = \realpath(__DIR__.'/resources/static-web/'.$file);
        if (\DIRECTORY_SEPARATOR == '\\') {
            $path = '/'.\str_replace('\\', '/', $path);  // Windows compatibility
        }

        return 'file://'.$path;
    }
}
