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
 * @covers \HeadlessChromium\Browser
 * @covers \HeadlessChromium\Page
 */
class MouseApiTest extends BaseTestCase
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
    public function testClickLink()
    {
        // initial navigation
        $page = $this->openSitePage('b.html');
        $rect = $page
            ->evaluate('JSON.parse(JSON.stringify(document.querySelector("#a").getBoundingClientRect()));')
            ->getReturnValue();

        $page->mouse()->move($rect['x'], $rect['y'])->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        $this->assertEquals('a - test', $title);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testScroll()
    {
        // initial navigation
        $page = $this->openSitePage('bigLayout.html');
        usleep(200000);

        // scroll 100px down
        $page->mouse()->scrollDown(100);
        usleep(200000);

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();

        $this->assertEquals(100, $windowScrollY);

        // scrolling 100px up should revert the last action
        $page->mouse()->scrollUp(100);
        usleep(200000);

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();

        $this->assertEquals(0, $windowScrollY);
    }
}
