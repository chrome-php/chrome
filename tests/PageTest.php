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

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\InvalidTimezoneId;

/**
 * @covers \HeadlessChromium\Page
 */
class PageTest extends BaseTestCase
{
    public function testSetViewport(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser([
            'windowSize' => [500, 500],
        ]);

        $page = $browser->createPage();

        $page->setViewport(100, 300)->await();

        $response = $page->evaluate('[window.innerWidth, window.innerHeight]')->getReturnValue();

        $this->assertEquals([100, 300], $response);
    }

    public function testSetUserAgent(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();

        $pageFooBar = $browser->createPage();
        $pageBarBaz = $browser->createPage();

        $pageFooBar->setUserAgent('foobar')->await();
        $pageBarBaz->setUserAgent('barbaz')->await();

        $pageFooBar->navigate(self::sitePath('a.html'))->waitForNavigation();
        $pageBarBaz->navigate(self::sitePath('a.html'))->waitForNavigation();

        $value1 = $pageFooBar->evaluate('navigator.userAgent')->getReturnValue();
        $value2 = $pageBarBaz->evaluate('navigator.userAgent')->getReturnValue();

        $this->assertEquals('foobar', $value1);
        $this->assertEquals('barbaz', $value2);
    }

    public function testSetTimezone(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();

        $page = $browser->createPage();

        $page->evaluate('
            globalThis.date = new Date(1479579154987);
        ');

        $page->setTimezone('America/Jamaica');
        $this->assertEquals(
            'Sat Nov 19 2016 13:12:34 GMT-0500 (Eastern Standard Time)',
            $page->evaluate('date.toString()')->getReturnValue()
        );

        $page->setTimezone('Pacific/Honolulu');
        $this->assertEquals(
            'Sat Nov 19 2016 08:12:34 GMT-1000 (Hawaii-Aleutian Standard Time)',
            $page->evaluate('date.toString()')->getReturnValue()
        );

        $page->setTimezone('America/Buenos_Aires');
        $this->assertEquals(
            'Sat Nov 19 2016 15:12:34 GMT-0300 (Argentina Standard Time)',
            $page->evaluate('date.toString()')->getReturnValue()
        );

        $page->setTimezone('Europe/Berlin');
        $this->assertEquals(
            'Sat Nov 19 2016 19:12:34 GMT+0100 (Central European Standard Time)',
            $page->evaluate('date.toString()')->getReturnValue()
        );

        $page->setTimezone('Europe/Berlin');
        $this->assertEquals(
            'Sat Nov 19 2016 19:12:34 GMT+0100 (Central European Standard Time)',
            $page->evaluate('date.toString()')->getReturnValue()
        );

        $page->setTimezone('gteged');
    }

    public function testSetTimezoneInvalid(): void
    {
        $this->expectException(InvalidTimezoneId::class);

        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();

        $page = $browser->createPage();

        $page->setTimezone('Foo/Bar');
        $this->expectExceptionMessage('Invalid Timezone ID: Foo/Bar');

        $page->setTimezone('Baz/Qux');
        $this->expectExceptionMessage('Invalid Timezone ID: Baz/Qux');
    }

    public function testPreScriptOption(): void
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
        $page->navigate(self::sitePath('a.html'))->waitForNavigation();
        $fooValue = $page->evaluate('navigator.foo')->getReturnValue();
        $barValue = $page->evaluate('navigator.bar')->getReturnValue();
        $this->assertEquals(1, $fooValue);
        $this->assertEquals(11, $barValue);

        // make sure prescript is not adding again and again on every requests
        $page->navigate(self::sitePath('b.html'))->waitForNavigation();
        $fooValue = $page->evaluate('navigator.foo')->getReturnValue();
        $barValue = $page->evaluate('navigator.bar')->getReturnValue();
        $this->assertEquals(1, $fooValue);
        $this->assertEquals(11, $barValue);

        // make sure prescript did not pollute other pages
        $page2->navigate(self::sitePath('b.html'))->waitForNavigation();
        $fooValue = $page2->evaluate('navigator.foo')->getReturnValue();
        $barValue = $page2->evaluate('navigator.bar')->getReturnValue();
        $this->assertEquals(null, $fooValue);
        $this->assertEquals(null, $barValue);
    }

    public function testCallFunction(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();
        $evaluation = $page->callFunction('function(a, b) { window.foo = a + b; return window.foo;}', [1, 2]);

        $this->assertEquals(3, $evaluation->getReturnValue());
        $this->assertEquals(3, $page->evaluate('window.foo')->getReturnValue());
    }

    public function testCallFunctionPromise(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();
        $evaluation = $page->callFunction('function(a, b) {
            return new Promise(resolve => {
                setTimeout(() => {
                    resolve(a + b);
                }, 100);
            })
        }', [1, 2]);

        $this->assertEquals(3, $evaluation->getReturnValue());
    }

    public function testEvaluatePromise(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();
        $evaluation = $page->evaluate('new Promise(resolve => {
            setTimeout(() => {
                resolve(11);
            }, 100);
        })');

        $this->assertEquals(11, $evaluation->getReturnValue());
    }

    public function testAddScriptTagContent(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();
        $page->addScriptTag([
            'content' => 'window.foo = "bar";',
        ])->waitForResponse();

        $this->assertEquals('bar', $page->evaluate('window.foo')->getReturnValue());
    }

    public function testAddScriptTagUrl(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();
        $page->navigate(
            self::sitePath('a.html')
        )->waitForNavigation();

        $page->addScriptTag([
            'url' => self::sitePath('jsInclude.js'),
        ])->waitForResponse();

        $isIncluded = $page->evaluate('window.testJsIsIncluded')->getReturnValue();
        $scriptSrc = $page->evaluate('document.querySelector("script").getAttribute("src")')->getReturnValue();

        $this->assertEquals('isIncluded', $isIncluded);
        $this->assertStringStartsWith('file://', $scriptSrc);
        $this->assertStringEndsWith('/jsInclude.js', $scriptSrc);
    }

    public function testGetLayoutMetrics(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser([
            'windowSize' => [500, 500],
        ]);

        $page = $browser->createPage();
        $page->navigate(self::sitePath('bigLayout.html'))->waitForNavigation();

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
                'height' => 1000,
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
                'zoom' => 1,
            ],
            $visualViewport
        );
    }

    public function testGetFullPageClip(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser([
            'windowSize' => [500, 500],
        ]);

        $page = $browser->createPage();
        $page->navigate(self::sitePath('bigLayout.html'))->waitForNavigation();

        $clip = $page->getFullPageClip();

        $this->assertEquals(0, $clip->getX());
        $this->assertEquals(0, $clip->getY());
        $this->assertEquals(900, $clip->getWidth());
        $this->assertEquals(1000, $clip->getHeight());
    }

    public function testInvalidScaleOptionThrowAnException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $factory = new BrowserFactory();
        $browser = $factory->createBrowser();
        $page = $browser->createPage();
        $page->pdf(['scale' => '2px']);
    }

    public function testGetHtml(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();

        $page->navigate(self::sitePath('index.html'))->waitForNavigation();

        $this->assertStringContainsString('<h1>bar</h1>', $page->getHtml());
    }
}
