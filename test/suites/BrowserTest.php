<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\Browser;
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Socket\MockSocket;
use HeadlessChromium\Page;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HeadlessChromium\Browser
 */
class BrowserTest extends TestCase
{
    
    public function testBrowser()
    {
        $connection = new Connection(new MockSocket());

        $browser = new Browser($connection);
        $this->assertSame($connection, $browser->getConnection());
    }
    
    public function testCreatePage()
    {
        $mockSocket = new MockSocket();
        $connection = new Connection($mockSocket);
        $connection->connect();

        $browser = new Browser($connection);

        // set received data for Target.createPage and AttachTargetTo
        $mockSocket->addReceivedData(json_encode(['result' => ['targetId' => 'foo-bar']]), true);
        $mockSocket->addReceivedData(json_encode(['result' => ['sessionId' => 'baz-qux']]), true);

        $page = $browser->createPage();

        // test page
        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('foo-bar', $page->getSession()->getTargetId());
        $this->assertEquals('baz-qux', $page->getSession()->getSessionId());

        // assert data sent
        $this->assertDataSentEquals(
            [
                [
                    'id' => '%id',
                    'method' => 'Target.createTarget',
                    'params' => [
                        'url' => 'about:blank'
                    ]
                ],
                [
                    'id' => '%id',
                    'method' => 'Target.attachToTarget',
                    'params' => [
                        'targetId' => 'foo-bar'
                    ]
                ]
            ],
            $mockSocket->getSentData()
        );
    }

    private function assertDataSentEquals(array $assert, array $sent)
    {
        // count must match
        $this->assertEquals(
            count($assert),
            count($sent),
            sprintf(
                'Failed asserting that %s messages sent matches expected %s messages sent',
                count($sent),
                count($assert)
            )
        );

        // decode sent data
        foreach ($sent as $k => $datum) {
            $sent[$k] = json_decode($datum, true);
            $this->assertInternalType('array', $sent[$k]);
        }

        // test sent data is equal to
        foreach ($assert as $k => $datum) {
            // replace id with message id
            if (isset($datum['id']) && $datum['id'] == '%id') {
                $this->assertArrayHasKey('id', $sent[$k]);
                $datum['id'] = $sent[$k]['id'];
            }

            $this->assertEquals($datum, $sent[$k]);
        }
    }
}
