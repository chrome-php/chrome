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

use Generator;
use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Dom\Selector\CssSelector;
use HeadlessChromium\Dom\Selector\Selector;
use HeadlessChromium\Dom\Selector\XPathSelector;

/**
 * @covers \HeadlessChromium\Browser
 * @covers \HeadlessChromium\Page
 */
class MouseApiTest extends BaseTestCase
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
    public function testClickLink(): void
    {
        // initial navigation
        $page = $this->openSitePage('b.html');
        $rect = $page
            ->evaluate('JSON.parse(JSON.stringify(document.querySelector("#a").getBoundingClientRect()));')
            ->getReturnValue();

        $page->mouse()->move(\ceil($rect['x']), \ceil($rect['y']))->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        self::assertSame('a - test', $title);
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

        self::assertSame(100, $windowScrollY);
        self::assertSame(100, $page->mouse()->getPosition()['y']);

        // scrolling 100px up should revert the last action
        $page->mouse()->scrollUp(100);

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();

        self::assertSame(0, $windowScrollY);
        self::assertSame(0, $page->mouse()->getPosition()['y']);

        // try to scroll more than possible
        $page->mouse()->scrollDown(10000);

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();

        self::assertLessThan(10000, $windowScrollY);
        self::assertLessThan(10000, $page->mouse()->getPosition()['y']);
    }

    /**
     * Scrolling works when scrollable area shrinks immediatly after scroll event.
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function testScrollDoesNotTimeOutWhenScrollableAreaShrinks(): void
    {
        $page = $this->openSitePage('scrollShrink.html');

        $page->mouse()->scrollDown(4000); // Before patch this threw an OperationTimedOut Exception.

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();
        $maximumY = $page
            ->evaluate('document.documentElement.scrollHeight - window.innerHeight')
            ->getReturnValue();

        // We asked to scroll further than the shrunken page allows, so we end up
        // exactly at the new (smaller) maximum. The actual regression guarantee
        // is that scrollDown() returns at all instead of timing out.
        self::assertSame($maximumY, $windowScrollY);
    }

    /**
     * Scrolling works when an overlay pops up and locks scrolling immediatly after scroll event.
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function testScrollDoesNotTimeOutWhenModalLocksScrolling(): void
    {
        $page = $this->openSitePage('scrollLock.html');

        $page->mouse()->scrollDown(4000); // Before patch this threw an OperationTimedOut Exception.

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();
        $maximumY = $page
            ->evaluate('document.documentElement.scrollHeight - window.innerHeight')
            ->getReturnValue();

        // Scrolling got locked, so the page can no longer be scrolled at all and
        // we stay at the top. The guarantee the fix restores is that scrollDown()
        // returns instead of timing out.
        self::assertSame(0, $maximumY);
        self::assertSame(0, $windowScrollY);
    }

    /**
     * Scrolling works when the layout shifts (e.g. due to lazy loaded image) during scrolling.
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function testScrollDoesNotTimeOutWhenLayoutShiftsDuringScroll(): void
    {
        $page = $this->openSitePage('infiniteScroll.html');

        $page->mouse()->scrollDown(4000); // Before patch this threw an OperationTimedOut Exception.

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();
        $maximumY = $page
            ->evaluate('document.documentElement.scrollHeight - window.innerHeight')
            ->getReturnValue();

        // The page grew (it did not shrink), so we scrolled and ended up at a
        // valid position within the new bounds rather than timing out. The exact
        // position is timing-dependent.
        self::assertGreaterThan(0, $windowScrollY);
        self::assertLessThanOrEqual($maximumY, $windowScrollY);
    }

    /**
     * @dataProvider providerFindElementWithSingleElement
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testFindElement_withSingleElement(Selector $selector): void
    {
        // initial navigation
        $page = $this->openSitePage('b.html');

        $page->mouse()->findElement($selector)->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        self::assertSame('a - test', $title);
    }

    /**
     * @return Generator<string, array{Selector}>
     */
    public static function providerFindElementWithSingleElement(): Generator
    {
        yield 'css' => [new CssSelector('#a')];
        yield 'xpath' => [new XPathSelector('//*[@id="a"]')];
    }

    /**
     * @dataProvider providerFindElementAfterMove
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testFindElementAfterMove(Selector $selector): void
    {
        // initial navigation
        $page = $this->openSitePage('b.html');

        $page->mouse()->move(1000, 1000);

        $page->mouse()->findElement($selector)->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        self::assertSame('a - test', $title);
    }

    /**
     * @return Generator<string, array{Selector}>
     */
    public static function providerFindElementAfterMove(): Generator
    {
        yield 'css' => [new CssSelector('#a')];
        yield 'xpath' => [new XPathSelector('//*[@id="a"]')];
    }

    /**
     * @dataProvider providerFindElementWithMultipleElements
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testFindElementWithMultipleElements(Selector $selector, int $position, string $expectedPageTitle): void
    {
        $page = $this->openSitePage('b.html');

        $page->mouse()->findElement($selector, $position)->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        self::assertSame($expectedPageTitle, $title);
    }

    /**
     * @return Generator<array-key, array{Selector, int, string}>
     */
    public static function providerFindElementWithMultipleElements(): Generator
    {
        $cssSelector = new CssSelector('.a');
        $xPathSelector = new XPathSelector('//*[@class="a"]');

        foreach (['css' => $cssSelector, 'xpath' => $xPathSelector] as $type => $selector) {
            yield $type.' – 1' => [$selector, -1, 'c - test'];
            yield $type.' – 2' => [$selector, 2, 'b - test'];
            yield $type.' – 3' => [$selector, 99, 'a - test'];
        }
    }

    /**
     * @dataProvider providerFindElementWithScrolling
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testFindElementWithScrolling(Selector $selector): void
    {
        // initial navigation
        $page = $this->openSitePage('bigLayout.html');

        $page->mouse()->findElement($selector);

        $page->mouse()->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        self::assertSame('a - test', $title);
    }

    /**
     * @return Generator<string, array{Selector}>
     */
    public static function providerFindElementWithScrolling(): Generator
    {
        yield 'css' => [new CssSelector('#bottomLink')];
        yield 'xpath' => [new XPathSelector('//*[@id="bottomLink"]')];
    }

    /**
     * @dataProvider providerFindElementWithMissingElement
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\ElementNotFoundException
     */
    public function testFindElementWithMissingElement(Selector $selector): void
    {
        $this->expectException(\HeadlessChromium\Exception\ElementNotFoundException::class);

        // initial navigation
        $page = $this->openSitePage('b.html');

        $page->mouse()->findElement($selector);
    }

    /**
     * @return Generator<string, array{Selector}>
     */
    public static function providerFindElementWithMissingElement(): Generator
    {
        yield 'css' => [new CssSelector('#missing')];
        yield 'xpath' => [new XPathSelector('//*[@id="missing"]')];
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testGetPosition(): void
    {
        // initial navigation
        $page = $this->openSitePage('b.html');

        self::assertEquals(['x' => 0, 'y' => 0], $page->mouse()->getPosition());

        // find element with id "a"
        $page->mouse()->find('#a');

        $x = $page->mouse()->getPosition()['x'];
        $y = $page->mouse()->getPosition()['y'];

        self::assertGreaterThanOrEqual(1, $x); // 8
        self::assertLessThanOrEqual(51, $x);

        self::assertGreaterThanOrEqual(1, $y); // 87
        self::assertLessThanOrEqual(107, $y);
    }
}
