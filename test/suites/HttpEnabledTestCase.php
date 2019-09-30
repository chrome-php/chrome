<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test;

use Symfony\Component\Process\Process;

class HttpEnabledTestCase extends BaseTestCase
{

    /** @var Process */
    private static $process;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $command = 'php -S localhost:8083 -t ' . __DIR__ . '/../resources/static-web';

        self::$process = method_exists(Process::class, 'fromShellCommandline')
            ? Process::fromShellCommandline($command)
            : new Process($command);

        self::$process->start();
        usleep(10000); //wait for server to get going
    }

    public static function tearDownAfterClass()
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
