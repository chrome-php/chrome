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

use finfo;
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

        self::assertEquals([100, 300], $response);
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

        self::assertEquals('foobar', $value1);
        self::assertEquals('barbaz', $value2);
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
        self::assertStringStartsWith(
            'Sat Nov 19 2016 13:12:34 GMT-0500',
            $page->evaluate('date.toString()')->getReturnValue()
        );

        $page->setTimezone('Pacific/Honolulu');
        self::assertStringStartsWith(
            'Sat Nov 19 2016 08:12:34 GMT-1000',
            $page->evaluate('date.toString()')->getReturnValue()
        );

        $page->setTimezone('America/Buenos_Aires');
        self::assertStringStartsWith(
            'Sat Nov 19 2016 15:12:34 GMT-0300',
            $page->evaluate('date.toString()')->getReturnValue()
        );

        $page->setTimezone('Europe/Berlin');
        self::assertStringStartsWith(
            'Sat Nov 19 2016 19:12:34 GMT+0100',
            $page->evaluate('date.toString()')->getReturnValue()
        );

        $page->setTimezone('Europe/Berlin');
        self::assertStringStartsWith(
            'Sat Nov 19 2016 19:12:34 GMT+0100',
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
        self::assertEquals(1, $fooValue);
        self::assertEquals(11, $barValue);

        // make sure prescript is not adding again and again on every requests
        $page->navigate(self::sitePath('b.html'))->waitForNavigation();
        $fooValue = $page->evaluate('navigator.foo')->getReturnValue();
        $barValue = $page->evaluate('navigator.bar')->getReturnValue();
        self::assertEquals(1, $fooValue);
        self::assertEquals(11, $barValue);

        // make sure prescript did not pollute other pages
        $page2->navigate(self::sitePath('b.html'))->waitForNavigation();
        $fooValue = $page2->evaluate('navigator.foo')->getReturnValue();
        $barValue = $page2->evaluate('navigator.bar')->getReturnValue();
        self::assertEquals(null, $fooValue);
        self::assertEquals(null, $barValue);
    }

    public function testCallFunction(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();
        $evaluation = $page->callFunction('function(a, b) { window.foo = a + b; return window.foo;}', [1, 2]);

        self::assertEquals(3, $evaluation->getReturnValue());
        self::assertEquals(3, $page->evaluate('window.foo')->getReturnValue());
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

        self::assertEquals(3, $evaluation->getReturnValue());
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

        self::assertEquals(11, $evaluation->getReturnValue());
    }

    public function testAddScriptTagContent(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();
        $page->addScriptTag([
            'content' => 'window.foo = "bar";',
        ])->waitForResponse();

        self::assertEquals('bar', $page->evaluate('window.foo')->getReturnValue());
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

        self::assertEquals('isIncluded', $isIncluded);
        self::assertStringStartsWith('file://', $scriptSrc);
        self::assertStringEndsWith('/jsInclude.js', $scriptSrc);
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
        $cssContentSize = $metrics->getCssContentSize();
        $cssLayoutViewport = $metrics->getCssLayoutViewport();
        $cssVisualViewport = $metrics->getCssVisualViewport();

        self::assertEquals(
            [
                'x' => 0,
                'y' => 0,
                'width' => 900,
                'height' => 1000,
            ],
            $contentSize
        );

        self::assertEquals(
            [
                'pageX' => 0,
                'pageY' => 0,
                'clientWidth' => 100,
                'clientHeight' => 300,
            ],
            $layoutViewport
        );

        self::assertEquals(
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

        // This is made to be a bit loose to pass on retina displays

        self::assertContains(
            $cssContentSize,
            [
                [
                    'x' => 0,
                    'y' => 0,
                    'width' => 900,
                    'height' => 1000,
                ],
                [
                    'x' => 0,
                    'y' => 0,
                    'width' => 1800,
                    'height' => 2000,
                ],
            ]
        );

        self::assertContains(
            $cssLayoutViewport,
            [
                [
                    'pageX' => 0,
                    'pageY' => 0,
                    'clientWidth' => 100,
                    'clientHeight' => 300,
                ],
                [
                    'pageX' => 0,
                    'pageY' => 0,
                    'clientWidth' => 200,
                    'clientHeight' => 600,
                ],
            ]
        );

        self::assertContains(
            $cssVisualViewport,
            [
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
                [
                    'offsetX' => 0,
                    'offsetY' => 0,
                    'pageX' => 0,
                    'pageY' => 0,
                    'clientWidth' => 200,
                    'clientHeight' => 600,
                    'scale' => 1,
                    'zoom' => 1,
                ],
            ]
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

        self::assertEquals(0, $clip->getX());
        self::assertEquals(0, $clip->getY());
        self::assertEquals(900, $clip->getWidth());
        self::assertEquals(1000, $clip->getHeight());
    }

    public function testPdf(): void
    {
        $finfo = new finfo(\FILEINFO_MIME_TYPE);
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();

        $page->navigate(self::sitePath('index.html'))->waitForNavigation();

        $pagePdf = $page->pdf(['landscape' => false]);

        $pdf = $pagePdf->getBase64();
        $mimeType = $finfo->buffer(\base64_decode($pdf));

        self::assertSame('application/pdf', $mimeType);
    }

    public function testGetHtml(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();

        $page->navigate(self::sitePath('index.html'))->waitForNavigation();

        self::assertStringContainsString('<h1>bar</h1>', $page->getHtml());
    }

    public function testSetHtml(): void
    {
        $html = '<p>set html test</p>';
        $factory = new BrowserFactory();

        $page = $factory->createBrowser()->createPage();
        $page->setHtml($html);

        self::assertStringContainsString($html, $page->getHtml());
    }

    public function testWaitUntilContainsElement(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();

        $page->navigate(self::sitePath('elementLoad.html'))->waitForNavigation();

        $page->waitUntilContainsElement('div[data-name=\"el\"]');

        self::assertStringContainsString('<div data-name="el"></div>', $page->getHtml());
    }

    public function testSetExtraHTTPHeaders(): void
    {
        $factory = new BrowserFactory();

        $page = $factory->createBrowser()->createPage();
        $page->setExtraHTTPHeaders(['test' => 'test']);

        $this->expectNotToPerformAssertions();
    }

    public function testFindTarget(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();
        $page->navigate($this->sitePath('bigLayout.html'))->waitForNavigation();

        $target = $browser->findTarget('page', 'bigLayout.html');
        self::assertSame('bigLayout.html', $target->getTargetInfo('title'));
    }

    public function testSetJavaScriptEnabled(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();
        $page = $browser->createPage();

        $page->setJavaScriptEnabled(false);
        $page->navigate($this->sitePath('javascript.html'))->waitForNavigation();

        self::assertEquals(
            'javascript disabled',
            $page->evaluate('document.body.innerText')->getReturnValue()
        );

        $page->setJavaScriptEnabled(true);
        $page->navigate($this->sitePath('javascript.html'))->waitForNavigation();

        self::assertEquals(
            'javascript enabled',
            $page->evaluate('document.body.innerText')->getReturnValue()
        );
    }
}
