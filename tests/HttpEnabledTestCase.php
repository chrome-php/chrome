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

use Symfony\Component\Process\Process;

class HttpEnabledTestCase extends BaseTestCase
{
    /** @var Process */
    private static $process;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$process = new Process([
            'php',
            '-S',
            'localhost:8083',
            '-t',
            __DIR__ . '/resources/static-web'
        ]);
        self::$process->start();
        usleep(80000); //wait for server to get going

        // ensure it started
        if (!self::$process->isRunning()) {
            $message = self::$process->getErrorOutput();
            throw new \Exception('Cannot start webserver for tests: ' . $message);
        }
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        self::$process->stop();
    }

    public function getHttpHost()
    {
        return 'localhost:8083';
    }

    protected function sitePath($file)
    {
        return 'http://localhost:8083/' . $file;
    }
}
