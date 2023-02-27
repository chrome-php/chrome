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
use HeadlessChromium\Communication\Target;

/**
 * @covers \HeadlessChromium\BrowserFactory
 * @covers \HeadlessChromium\Browser\BrowserProcess
 */
class BrowserFactoryTest extends BaseTestCase
{
    public function testBrowserFactory(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();

        self::assertMatchesRegularExpression('#^ws://#', $browser->getSocketUri());
    }

    public function testWindowSizeOption(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser([
            'windowSize' => [1212, 333],
        ]);

        $page = $browser->createPage();

        $response = $page->evaluate('[window.outerHeight, window.outerWidth]')->getReturnValue();

        self::assertEquals([333, 1212], $response);
    }

    public function testUserAgentOption(): void
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser([
            'userAgent' => 'foo bar baz',
        ]);

        $page = $browser->createPage();

        $response = $page->evaluate('navigator.userAgent')->getReturnValue();

        self::assertEquals('foo bar baz', $response);
    }

    public function testAddHeaders(): void
    {
        $factory = new BrowserFactory();

        $factory->addHeader('header_name', 'header_value');
        $factory->addHeaders(['header_name2' => 'header_value2']);
        $factory->createBrowser()->createPage();

        $expected = [
            'header_name' => 'header_value',
            'header_name2' => 'header_value2',
        ];

        self::assertSame($expected, $factory->getOptions()['headers']);
    }

    public function testOptions(): void
    {
        $factory = new BrowserFactory();

        $headers = ['header_name' => 'header_value'];
        $options = ['userAgent' => 'foo bar baz'];
        $modifiedOptions = ['userAgent' => 'foo bar'];

        $factory->addHeaders($headers);
        $factory->addOptions($options);
        $factory->createBrowser()->createPage();

        $expected = \array_merge(['headers' => $headers], $options);

        self::assertSame($expected, $factory->getOptions());

        // test overwriting
        $factory->addOptions($modifiedOptions);
        $factory->createBrowser()->createPage();

        $expected['userAgent'] = 'foo bar';

        self::assertSame($expected, $factory->getOptions());

        // test removing options
        $factory->setOptions($modifiedOptions);
        $factory->createBrowser()->createPage();

        self::assertSame($modifiedOptions, $factory->getOptions());

        $factory->setOptions([]);
        $factory->createBrowser()->createPage();

        self::assertSame([], $factory->getOptions());
    }

    public function testConnectToBrowser(): void
    {
        // create a browser
        $factory = new BrowserFactory();
        $browser = $factory->createBrowser();

        // TODO test existing pages propagation

        // create a new connectionn to the existing browser
        $browser2 = BrowserFactory::connectToBrowser($browser->getSocketUri());

        // create a page on the first browser after 2d connection
        $page2 = $browser->createPage();
        $page2TargetId = $page2->getSession()->getTargetId();

        // update 2d browser
        $browser2->getConnection()->readData();

        // make sure 2nd browser received the new page
        $target = $browser2->getTarget($page2TargetId);
        self::assertInstanceOf(Target::class, $target);
    }
}
