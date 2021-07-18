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
use HeadlessChromium\Page;

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
    public function testClickLink(): void
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
    public function testScroll(): void
    {
        // initial navigation
        $page = $this->openSitePage('bigLayout.html');

        // scroll 100px down
        $page->mouse()->scrollDown(100);

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();

        $this->assertEquals(100, $windowScrollY);

        // scrolling 100px up should revert the last action
        $page->mouse()->scrollUp(100);

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();

        $this->assertEquals(0, $windowScrollY);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testFind_withSingleElement(): void
    {
        // initial navigation
        $page = $this->openSitePage('b.html');

        $page->mouse()->find('#a')->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        $this->assertEquals('a - test', $title);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testFind_withMultipleElements(): void
    {
        // initial navigation
        $page = $this->openSitePage('b.html');

        // click on the second element with class "a"
        $page->mouse()->find('.a', 1)->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        $this->assertEquals('a - test', $title);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
    */
    public function testFind_withPositionOutOfBounds(): void
    {
        // initial navigation
        $page = $this->openSitePage('b.html');

        // click on last element with class "a"
        $page->mouse()->find('.a', 999)->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        $this->assertEquals('a - test', $title);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testFind_withScrolling(): void
    {
        // initial navigation
        $page = $this->openSitePage('bigLayout.html');

        $page->mouse()->find('#bottomLink');
        \usleep(1000000);

        $page->mouse()->click();
        $page->waitForReload(Page::LOAD, 5000);

        $title = $page->evaluate('document.title')->getReturnValue();

        $this->assertEquals('a - test', $title);
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\ElementNotFoundException
    */
    public function testFind_withMissingElement(): void
    {
        $this->expectException(\HeadlessChromium\Exception\ElementNotFoundException::class);

        // initial navigation
        $page = $this->openSitePage('b.html');

        $page->mouse()->find('#missing');
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
    */
    public function testGetPosition(): void
    {
        // initial navigation
        $page = $this->openSitePage('b.html');

        $this->assertEquals(['x' => 0, 'y' => 0], $page->mouse()->getPosition());

        // find element with id "a"
        $page->mouse()->find('#a');
        \usleep(400000);

        $x = $page->mouse()->getPosition()['x'];
        $y = $page->mouse()->getPosition()['y'];

        $this->assertGreaterThanOrEqual(8, $x);
        $this->assertLessThanOrEqual(51, $x);

        $this->assertGreaterThanOrEqual(87, $y);
        $this->assertLessThanOrEqual(107, $y);
    }
}
