<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Session;
use HeadlessChromium\Communication\Socket\MockSocket;
use HeadlessChromium\Communication\Target;
use HeadlessChromium\Page;

/**
 * @covers \HeadlessChromium\Page
 */
class PageTest extends BaseTestCase
{

    public function testSetViewport()
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser([
            'windowSize' => [500, 500]
        ]);

        $page = $browser->createPage();

        $page->setViewport(100, 300)->await();

        $response = $page->evaluate('[window.innerWidth, window.innerHeight]')->getReturnValue();

        $this->assertEquals([100, 300], $response);
    }

    public function testSetUserAgent()
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();

        $pageFooBar = $browser->createPage();
        $pageBarBaz = $browser->createPage();

        $pageFooBar->setUserAgent('foobar')->await();
        $pageBarBaz->setUserAgent('barbaz')->await();

        $pageFooBar->navigate($this->sitePath('a.html'))->waitForNavigation();
        $pageBarBaz->navigate($this->sitePath('a.html'))->waitForNavigation();

        $value1 = $pageFooBar->evaluate('navigator.userAgent')->getReturnValue();
        $value2 = $pageBarBaz->evaluate('navigator.userAgent')->getReturnValue();

        $this->assertEquals('foobar', $value1);
        $this->assertEquals('barbaz', $value2);
    }


    public function testPreScriptOption()
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();

        $preScript1 =
            "if(!('foo' in navigator)) {
                navigator.foo = 0
            }
            navigator.foo++;";

        $preScript2 =
            "if(!('bar' in navigator)) {
                navigator.bar = 10
            }
            navigator.bar++;";

        $page = $browser->createPage();
        $page2 = $browser->createPage();
        $page->addPreScript($preScript1);
        $page->addPreScript($preScript2);

        // make sure prescript evaluates
        $page->navigate($this->sitePath('a.html'))->waitForNavigation();
        $fooValue = $page->evaluate('navigator.foo')->getReturnValue();
        $barValue = $page->evaluate('navigator.bar')->getReturnValue();
        $this->assertEquals(1, $fooValue);
        $this->assertEquals(11, $barValue);

        // make sure prescript is not adding again and again on every requests
        $page->navigate($this->sitePath('b.html'))->waitForNavigation();
        $fooValue = $page->evaluate('navigator.foo')->getReturnValue();
        $barValue = $page->evaluate('navigator.bar')->getReturnValue();
        $this->assertEquals(1, $fooValue);
        $this->assertEquals(11, $barValue);

        // make sure prescript did not pollute other pages
        $page2->navigate($this->sitePath('b.html'))->waitForNavigation();
        $fooValue = $page2->evaluate('navigator.foo')->getReturnValue();
        $barValue = $page2->evaluate('navigator.bar')->getReturnValue();
        $this->assertEquals(null, $fooValue);
        $this->assertEquals(null, $barValue);
    }

    public function testGetLayoutMetrics()
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser([
            'windowSize' => [500, 500]
        ]);

        $page = $browser->createPage();
        $page->navigate($this->sitePath('bigLayout.html'))->waitForNavigation();

        $page->setViewport(100, 300)->await();

        $metrics = $page->getLayoutMetrics();

        $contentSize = $metrics->getContentSize();
        $layoutViewport = $metrics->getLayoutViewport();
        $visualViewport = $metrics->getVisualViewport();

        $this->assertEquals(
            [
                'x' => 0,
                'y' => 0,
                'width' => 900,
                'height' => 1000
            ],
            $contentSize
        );

        $this->assertEquals(
            [
                'pageX' => 0,
                'pageY' => 0,
                'clientWidth' => 100,
                'clientHeight' => 300,
            ],
            $layoutViewport
        );

        $this->assertEquals(
            [
                'offsetX' => 0,
                'offsetY' => 0,
                'pageX' => 0,
                'pageY' => 0,
                'clientWidth' => 100,
                'clientHeight' => 300,
                'scale' => 1,
            ],
            $visualViewport
        );
    }
}
