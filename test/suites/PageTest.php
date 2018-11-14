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

    public function testPage()
    {
        $connection = new Connection(new MockSocket());
        $session = new Session('foo', 'bar', $connection);
        $target = new Target([], $session);
        $page = new Page($target, []);

        $this->assertSame($session, $page->getSession());
    }

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

        $pageFooBar->navigate('http://requestbin.fullcontact.com/uhunfhuh')->waitForNavigation();
        $pageBarBaz->navigate('http://requestbin.fullcontact.com/uhunfhuh')->waitForNavigation();

        $value1 = $pageFooBar->evaluate('navigator.userAgent')->getReturnValue();
        $value2 = $pageBarBaz->evaluate('navigator.userAgent')->getReturnValue();

        $this->assertEquals('foobar', $value1);
        $this->assertEquals('barbaz', $value2);
    }
}
