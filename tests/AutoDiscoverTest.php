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

use HeadlessChromium\AutoDiscover;

/**
 * @covers \HeadlessChromium\AutoDiscover
 */
class AutoDiscoverTest extends BaseTestCase
{
    private ?string $originalEnvPath = null;

    protected function setUp(): void
    {
        if (\array_key_exists('CHROME_PATH', $_SERVER)) {
            $this->originalEnvPath = $_SERVER['CHROME_PATH'];

            unset($_SERVER['CHROME_PATH']);
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['CHROME_PATH']);

        if (null !== $this->originalEnvPath) {
            $_SERVER['CHROME_PATH'] = $this->originalEnvPath;
        }

        parent::tearDown();
    }

    public function testExplicitEnv(): void
    {
        $autoDiscover = new AutoDiscover();

        $_SERVER['CHROME_PATH'] = 'test-path';

        self::assertSame($_SERVER['CHROME_PATH'], $autoDiscover->guessChromeBinaryPath());
    }

    public function testLinux(): void
    {
        $autoDiscover = new AutoDiscover(function (): string {
            return 'Linux';
        });

        self::assertThat(
            $autoDiscover->guessChromeBinaryPath(),
            $this->logicalOr(
                'chrome',
                'google-chrome'
            )
          );
    }

    public function testMac(): void
    {
        $autoDiscover = new AutoDiscover(function (): string {
            return 'Darwin';
        });

        self::assertStringContainsString('.app', $autoDiscover->guessChromeBinaryPath());
    }

    public function testWindows(): void
    {
        $autoDiscover = new AutoDiscover(function (): string {
            return 'Windows';
        });

        self::assertStringContainsString('.exe', $autoDiscover->guessChromeBinaryPath());
    }
}
