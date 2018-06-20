<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\BrowserFactory;

/**
 * @covers \HeadlessChromium\BrowserFactory
 * @covers \HeadlessChromium\BrowserProcess
 */
class BrowserFactoryTest extends BaseTestCase
{

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
}
