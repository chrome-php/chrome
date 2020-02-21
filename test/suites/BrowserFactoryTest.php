<?php
/**
 * @license see LICENSE
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
    public function testBrowserFactory()
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser();

        $this->assertRegExp('#^ws://#', $browser->getSocketUri());
    }

    public function testWindowSizeOption()
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser([
            'windowSize' => [1212, 333]
        ]);

        $page = $browser->createPage();

        $response = $page->evaluate('[window.outerHeight, window.outerWidth]')->getReturnValue();

        $this->assertEquals([333, 1212], $response);
    }

    public function testUserAgentOption()
    {
        $factory = new BrowserFactory();

        $browser = $factory->createBrowser([
            'userAgent' => 'foo bar baz'
        ]);

        $page = $browser->createPage();

        $response = $page->evaluate('navigator.userAgent')->getReturnValue();

        $this->assertEquals('foo bar baz', $response);
    }

    public function testConnectToBrowser()
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
        $browser2->getConnection()->readData();  // Read again...otherwise getting the target below sometimes fails

        // make sure 2nd browser received the new page
        $target = $browser2->getTarget($page2TargetId);
        $this->assertInstanceOf(Target::class, $target);
    }
}
