<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\Browser;
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Session;
use HeadlessChromium\Communication\Socket\MockSocket;
use HeadlessChromium\Page;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HeadlessChromium\Page
 */
class PageTest extends TestCase
{
    
    public function testPage()
    {
        $connection = new Connection(new MockSocket());

        $session = new Session('foo', 'bar', $connection);

        $page = new Page($session);

        $this->assertSame($session, $page->getSession());
    }
}
