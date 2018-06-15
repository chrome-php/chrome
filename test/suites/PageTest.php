<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test;

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
}
