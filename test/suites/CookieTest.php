<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\CookiesCollection;

/**
 * @covers \HeadlessChromium\Browser
 * @covers \HeadlessChromium\Page
 */
class CookieTest extends HttpEnabledTestCase
{

    /**
     * @var Browser\ProcessAwareBrowser
     */
    public static $browser;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $factory = new BrowserFactory();
        self::$browser = $factory->createBrowser();
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        self::$browser->close();
    }

    private function openSitePage($file)
    {
        $page = self::$browser->createPage();
        $page->navigate($this->sitePath($file))->waitForNavigation();

        return $page;
    }

    public function testGetCookies()
    {
        // initial navigation
        $page = $this->openSitePage('cookie.html');

        $cookies = $page->readCookies()
            ->await()
            ->getCookies();

        $this->assertInstanceOf(CookiesCollection::class, $cookies);
        $this->assertCount(1, $cookies);

        $this->assertEquals($cookies->getAt(0)->getName(), 'foo');
        $this->assertEquals($cookies->getAt(0)->getValue(), 'bar');
    }
}
