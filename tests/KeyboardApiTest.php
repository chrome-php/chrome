<?php

declare(strict_types=1);

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;

/**
 * @covers \HeadlessChromium\Browser
 * @covers \HeadlessChromium\Page
 */
class KeyboardApiTest extends BaseTestCase
{
    /**
     * @var Browser\ProcessAwareBrowser
     */
    public static $browser;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $factory = new BrowserFactory();
        self::$browser = $factory->createBrowser();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::$browser->close();
    }

    private function openSitePage($file)
    {
        $page = self::$browser->createPage();
        $page->navigate(self::sitePath($file))->waitForNavigation();

        return $page;
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testTypeText(): void
    {
        // initial navigation
        $page = $this->openSitePage('form.html');

        $page->keyboard()
            ->typeRawKey('Tab')
            ->typeText('bar');

        $value = $page
            ->evaluate('document.querySelector("#myinput").value;')
            ->getReturnValue();

        // checks if the input contains the typed text
        $this->assertEquals('bar', $value);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testTypeRawKey(): void
    {
        // initial navigation
        $page = $this->openSitePage('form.html');

        // the initial focus should not be #myinput
        $value = $page
            ->evaluate('document.activeElement === document.querySelector("#myinput");')
            ->getReturnValue();

        $this->assertFalse($value);

        // press the Tab key
        $page->keyboard()->typeRawKey('Tab');

        // test the the focus switched to #myinput
        $value = $page
            ->evaluate('document.activeElement === document.querySelector("#myinput");')
            ->getReturnValue();

        $this->assertTrue($value);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testKeyInterval(): void
    {
        // initial navigation
        $page = $this->openSitePage('form.html');

        $start = \round(\microtime(true) * 1000);

        $page->keyboard()
            ->setKeyInterval(100)
            ->typeRawKey('Tab')
            ->typeText('bar');

        $millisecondsElapsed = \round(\microtime(true) * 1000) - $start;

        // if this test takes less than 300ms to run (3 keys x 100ms), setKeyInterval is not working
        $this->assertGreaterThan(300, $millisecondsElapsed);
    }
}
