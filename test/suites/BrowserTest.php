<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\Browser;
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Socket\MockSocket;
use HeadlessChromium\Page;

/**
 * @covers \HeadlessChromium\Browser
 */
class BrowserTest extends BaseTestCase
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
        $mockSocket->addReceivedData(json_encode(['method' => 'Target.targetCreated', 'params' => ['targetInfo' => ['targetId' => 'foo-bar']]]), false);
        $mockSocket->addReceivedData(json_encode(['result' => ['targetId' => 'foo-bar']]), true);
        $mockSocket->addReceivedData(json_encode(['result' => ['sessionId' => 'baz-qux']]), true);

        // Page.enable
        $mockSocket->addReceivedData(json_encode(['result' => []]), true);

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
                ],
                [
                    'id' => '%id',
                    'method' => 'Target.sendMessageToTarget',
                    'params' => [
                        'message' =>  json_encode([
                            'id' => '%id',
                            'method' => 'Page.enable',
                            'params' => []
                        ]),
                        'sessionId' => 'baz-qux'
                    ]
                ]
            ],
            $mockSocket->getSentData()
        );
    }
}
