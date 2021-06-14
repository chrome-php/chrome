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
    private $originalEnvPath = null;

    protected function setUp(): void
    {
        $this->originalEnvPath = null ?? $_SERVER['CHROME_PATH'];

        unset($_SERVER['CHROME_PATH']);

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

        $this->assertSame($_SERVER['CHROME_PATH'], $autoDiscover->getChromeBinaryPath());
    }

    public function testLinux(): void
    {
        $autoDiscover = $this->getMock('Linux');

        $this->assertSame('chrome', $autoDiscover->getChromeBinaryPath());
    }

    public function testMac(): void
    {
        $autoDiscover = $this->getMock('Darwin');

        $this->assertTrue($autoDiscover->isMac());
        $this->assertStringContainsString('.app', $autoDiscover->getChromeBinaryPath());
    }

    /**
     * @dataProvider windowsNameProvider
     */
    public function testWindows($phpOS): void
    {
        $autoDiscover = $this->getMock($phpOS);

        $this->assertTrue($autoDiscover->isWindows());
        $this->assertStringContainsString('.exe', $autoDiscover->getChromeBinaryPath());
    }

    public function windowsNameProvider(): array
    {
        return [
            ['WIN32'],
            ['WINNT'],
            ['Windows'],
        ];
    }

    private function getMock($os = 'Linux'): AutoDiscover
    {
        /** @var AutoDiscover&\PHPUnit\Framework\MockObject\MockObject $autoDiscover */
        $autoDiscover = $this->createPartialMock(
            AutoDiscover::class,
            ['getOS']
        );

        $autoDiscover->expects($this->any())->method('getOS')->willReturn($os);

        return $autoDiscover;
    }
}
