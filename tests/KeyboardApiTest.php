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

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;

/**
 * @covers \HeadlessChromium\Input\Keyboard
 */
class KeyboardApiTest extends BaseTestCase
{
    public static Browser\ProcessAwareBrowser $browser;

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
            ->type('Tab')
            ->typeText('bar');

        $value = $page
            ->evaluate('document.querySelector("#myinput").value;')
            ->getReturnValue();

        // checks if the input contains the typed text
        self::assertEquals('bar', $value);
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

        self::assertFalse($value);

        // press the Tab key
        $page->keyboard()->typeRawKey('Tab');

        // test the the focus switched to #myinput
        $value = $page
            ->evaluate('document.activeElement === document.querySelector("#myinput");')
            ->getReturnValue();

        self::assertTrue($value);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testTypeKeyCombinations(): void
    {
        // initial navigation
        $page = $this->openSitePage('form.html');

        $text = 'bar';

        // select an input and type a random text
        $page->keyboard()
            ->typeRawKey('Tab')
            ->typeText($text);

        // select all the text using ctrl + a
        $page->keyboard()
            ->press(' control ') // key names should be case insensitive and trimmed
                ->type('a')
            ->release('Control');

        // type ctrl + c to copy the selected text and paste it twice with ctrl + v
        $page->keyboard()
            ->press('Ctrl') // aliases sould work
                ->type('c')
                ->type('V') // upper and lower case should behave the same way
                ->type('v')
            ->release();

        $value = $page
            ->evaluate('document.querySelector("#myinput").value;')
            ->getReturnValue();

        // check if the input contains the typed text twice
        self::assertEquals($text.$text, $value);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testReleaseAll(): void
    {
        // initial navigation
        $page = $this->openSitePage('form.html');

        $page->keyboard()
            ->press('a')
            ->press('b')
            ->release();

        self::assertEquals(0, \count($page->keyboard()->getPressedKeys()));
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testKeyInterval(): void
    {
        // initial navigation
        $page = $this->openSitePage('form.html');

        $start = \round(\hrtime(true) / 1000 / 1000);

        $page->keyboard()
            ->setKeyInterval(100)
            ->typeRawKey('Tab')
            ->typeText('bar');

        $millisecondsElapsed = \round(\hrtime(true) / 1000 / 1000) - $start;

        // if this test takes less than 300ms to run (3 keys x 100ms), setKeyInterval is not working
        self::assertGreaterThan(300, $millisecondsElapsed);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     */
    public function testTypeUnicodeText(): void
    {
        // initial navigation
        $page = $this->openSitePage('form.html');

        $text = 'Со ГӀалгӀа ва';

        $page->keyboard()
            ->type('Tab')
            ->typeText($text);

        $value = $page
            ->evaluate('document.querySelector("#myinput").value;')
            ->getReturnValue();

        // checks if the input contains the typed text
        self::assertSame($text, $value);
    }
}
