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

        $page->mouse()->move(\ceil($rect['x']), \ceil($rect['y']))->click();
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
        $this->assertEquals(100, $page->mouse()->getPosition()['y']);

        // scrolling 100px up should revert the last action
        $page->mouse()->scrollUp(100);

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();

        $this->assertEquals(0, $windowScrollY);
        $this->assertEquals(0, $page->mouse()->getPosition()['y']);

        // try to scroll more than possible
        $page->mouse()->scrollDown(10000);

        $windowScrollY = $page->evaluate('window.scrollY')->getReturnValue();

        $this->assertLessThan(10000, $windowScrollY);
        $this->assertLessThan(10000, $page->mouse()->getPosition()['y']);
    }

    /**
     * @dataProvider providerFindElement_withSingleElement
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

        $this->assertEquals('a - test', $title);
    }

    /**
     * @return Generator<string, array{Selector}>
     */
    public function providerFindElement_withSingleElement(): Generator
    {
        yield 'css' => [new CssSelector('#a')];
        yield 'xpath' => [new XPathSelector('//*[@id="a"]')];
    }

    /**
     * @dataProvider providerFindElement_afterMove
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testFindElement_afterMove(Selector $selector): void
    {
        // initial navigation
        $page = $this->openSitePage('b.html');

        $page->mouse()->move(1000, 1000);

        $page->mouse()->findElement($selector)->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        $this->assertEquals('a - test', $title);
    }

    /**
     * @return Generator<string, array{Selector}>
     */
    public function providerFindElement_afterMove(): Generator
    {
        yield 'css' => [new CssSelector('#a')];
        yield 'xpath' => [new XPathSelector('//*[@id="a"]')];
    }

    /**
     * @dataProvider providerFindElement_withMultipleElements
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testFindElement_withMultipleElements(Selector $selector, int $position, string $expectedPageTitle): void
    {
        $page = $this->openSitePage('b.html');

        $page->mouse()->findElement($selector, $position)->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        $this->assertEquals($expectedPageTitle, $title);
    }

    /**
     * @return Generator<array-key, array{Selector, int, string}>
     */
    public function providerFindElement_withMultipleElements(): Generator
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
     * @dataProvider providerFindElement_withScrolling
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function testFindElement_withScrolling(Selector $selector): void
    {
        // initial navigation
        $page = $this->openSitePage('bigLayout.html');

        $page->mouse()->findElement($selector);

        $page->mouse()->click();
        $page->waitForReload();

        $title = $page->evaluate('document.title')->getReturnValue();

        $this->assertEquals('a - test', $title);
    }

    /**
     * @return Generator<string, array{Selector}>
     */
    public function providerFindElement_withScrolling(): Generator
    {
        yield 'css' => [new CssSelector('#bottomLink')];
        yield 'xpath' => [new XPathSelector('//*[@id="bottomLink"]')];
    }

    /**
     * @dataProvider providerFindElement_withMissingElement
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\ElementNotFoundException
     */
    public function testFindElement_withMissingElement(Selector $selector): void
    {
        $this->expectException(\HeadlessChromium\Exception\ElementNotFoundException::class);

        // initial navigation
        $page = $this->openSitePage('b.html');

        $page->mouse()->findElement($selector);
    }

    /**
     * @return Generator<string, array{Selector}>
     */
    public function providerFindElement_withMissingElement(): Generator
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

        $this->assertEquals(['x' => 0, 'y' => 0], $page->mouse()->getPosition());

        // find element with id "a"
        $page->mouse()->find('#a');

        $x = $page->mouse()->getPosition()['x'];
        $y = $page->mouse()->getPosition()['y'];

        $this->assertGreaterThanOrEqual(1, $x); // 8
        $this->assertLessThanOrEqual(51, $x);

        $this->assertGreaterThanOrEqual(1, $y); // 87
        $this->assertLessThanOrEqual(107, $y);
    }
}
