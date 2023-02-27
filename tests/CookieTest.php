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
use HeadlessChromium\Cookies\Cookie;
use HeadlessChromium\Cookies\CookiesCollection;

/**
 * @covers \HeadlessChromium\Browser
 * @covers \HeadlessChromium\PageUtils\CookiesGetter
 * @covers \HeadlessChromium\Page
 */
class CookieTest extends HttpEnabledTestCase
{
    /**
     * @var Browser\ProcessAwareBrowser
     */
    public static $browser;

    public function setUp(): void
    {
        parent::setUp();
        $factory = new BrowserFactory();
        self::$browser = $factory->createBrowser();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        self::$browser->close();
    }

    private function openSitePage($file)
    {
        $page = self::$browser->createPage();
        $page->navigate(self::sitePath($file))->waitForNavigation();

        return $page;
    }

    public function testReadCookies(): void
    {
        // initial navigation
        $page = $this->openSitePage('cookie.html');

        $cookies = $page->getCookies();

        self::assertInstanceOf(CookiesCollection::class, $cookies);
        self::assertCount(1, $cookies);

        self::assertEquals($cookies->getAt(0)->getName(), 'foo');
        self::assertEquals($cookies->getAt(0)->getValue(), 'bar');
    }

    public function testGetAllCookies(): void
    {
        // initial navigation
        $page = $this->openSitePage('cookie.html');

        $cookies = $page->getAllCookies();

        self::assertInstanceOf(CookiesCollection::class, $cookies);
        self::assertCount(1, $cookies);

        self::assertEquals($cookies->getAt(0)->getName(), 'foo');
        self::assertEquals($cookies->getAt(0)->getValue(), 'bar');
    }

    public function testSetCookies(): void
    {
        // initial navigation
        $page = self::$browser->createPage();

        // set cookie for arbitrary host
        $page->setCookies([
            Cookie::create('baz', 'qux', [
                'domain' => 'foo.bar',
                'expires' => \time() + 3600,
            ]),
        ])->await();

        $cookies = $page->getAllCookies();

        self::assertInstanceOf(CookiesCollection::class, $cookies);
        self::assertCount(1, $cookies);

        self::assertEquals($cookies->getAt(0)->getName(), 'baz');
        self::assertEquals($cookies->getAt(0)->getValue(), 'qux');
        self::assertEquals($cookies->getAt(0)->getDomain(), 'foo.bar');

        // Set cookie for current page
        $page->navigate(self::sitePath('a.html'))->waitForNavigation();

        $page->setCookies([
            Cookie::create('quux', 'corge'),
        ])->await();

        $cookies = $page->getAllCookies();

        self::assertInstanceOf(CookiesCollection::class, $cookies);
        self::assertCount(2, $cookies);

        self::assertEquals($cookies->getAt(1)->getName(), 'quux');
        self::assertEquals($cookies->getAt(1)->getValue(), 'corge');
        self::assertEquals($cookies->getAt(1)->getDomain(), 'localhost');

        self::assertEquals($cookies->getAt(0)->getName(), 'baz');
        self::assertEquals($cookies->getAt(0)->getValue(), 'qux');
        self::assertEquals($cookies->getAt(0)->getDomain(), 'foo.bar');
    }
}
