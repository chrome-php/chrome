<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Test\Communication;

use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Communication\Session;
use HeadlessChromium\Communication\Socket\MockSocket;
use HeadlessChromium\Exception\CommunicationException\CannotReadResponse;
use HeadlessChromium\Exception\CommunicationException\InvalidResponse;
use HeadlessChromium\Exception\OperationTimedOut;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HeadlessChromium\Communication\Connection
 */
class ConnectionTest extends TestCase
{
    /**
     * @var MockSocket
     */
    protected $mocSocket;

    public function setUp(): void
    {
        parent::setUp();
        $this->mocSocket = new MockSocket();
    }

    public function testIsStrict(): void
    {
        $connection = new Connection($this->mocSocket);
        $this->assertTrue($connection->isStrict());
        $connection->setStrict(false);
        $this->assertFalse($connection->isStrict());
    }

    public function testConnectDisconnect(): void
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();

        $this->assertTrue($this->mocSocket->isConnected());

        $connection->disconnect();

        $this->assertFalse($this->mocSocket->isConnected());
    }

    public function testCreateSession(): void
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();

        $this->mocSocket->addReceivedData(\json_encode(['result' => ['sessionId' => 'foo-bar']]), true);

        $session = $connection->createSession('baz-qux');

        $this->assertInstanceOf(Session::class, $session);
        $this->assertEquals('foo-bar', $session->getSessionId());
        $this->assertEquals('baz-qux', $session->getTargetId());
        $this->assertSame($connection, $session->getConnection());
    }

    public function testSendMessage(): void
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();

        $message = new Message('foo', ['bar' => 'baz']);

        $reader = $connection->sendMessage($message);

        $this->assertInstanceOf(ResponseReader::class, $reader);
        $this->assertSame($message, $reader->getMessage());
        $this->assertSame($connection, $reader->getConnection());

        $this->assertEquals(
            [
                \json_encode([
                    'id' => $message->getId(),
                    'method' => 'foo',
                    'params' => ['bar' => 'baz'],
                ]),
            ],
            $this->mocSocket->getSentData()
        );
    }

    /**
     * This test asserts that data are sent when a delay is set. It does not test that the delay works.
     */
    public function testSendMessageWorksWithDelay(): void
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();
        $connection->setConnectionDelay(1);

        $message = new Message('foo', ['bar' => 'baz']);

        $connection->sendMessage($message);
        $connection->sendMessage($message);

        $this->assertEquals(
            [
                \json_encode(
                    [
                        'id' => $message->getId(),
                        'method' => 'foo',
                        'params' => ['bar' => 'baz'],
                    ]
                ),
                \json_encode(
                    [
                        'id' => $message->getId(),
                        'method' => 'foo',
                        'params' => ['bar' => 'baz'],
                    ]
                ),
            ],
            $this->mocSocket->getSentData()
        );
    }

    public function testConnectionHttpHeaders(): void
    {
        $connection = new Connection($this->mocSocket);

        $header = [
            'header_name' => 'header_value',
        ];

        $connection->setConnectionHttpHeaders($header);

        $this->assertSame($header, $connection->getConnectionHttpHeaders());
    }

    public function testSendMessageSync(): void
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();

        $message = new Message('foo', ['bar' => 'baz']);

        $this->mocSocket->addReceivedData(\json_encode(['id' => $message->getId(), 'bar' => 'foo']));

        $response = $connection->sendMessageSync($message, 2);

        $this->assertSame($message, $response->getMessage());
        $this->assertEquals(['id' => $message->getId(), 'bar' => 'foo'], $response->getData());
    }

    public function testSendMessageSyncException(): void
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();

        $message = new Message('foo', ['bar' => 'baz']);

        $this->expectException(OperationTimedOut::class);

        $connection->sendMessageSync($message, 2);
    }

    public function testReadData(): void
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();

        $this->assertFalse($connection->readData());

        $this->mocSocket->addReceivedData(\json_encode([
            'id' => 1,
            'foo' => 'bar',
        ]));

        $this->assertTrue($connection->readData());
        $this->assertFalse($connection->readData());

        $this->assertTrue($connection->hasResponseForId(1));
        $this->assertTrue($connection->hasResponseForId(1)); // still true until read
        $this->assertFalse($connection->hasResponseForId(2));

        $data = $connection->getResponseForId(1);
        $this->assertEquals(
            [
                'id' => 1,
                'foo' => 'bar',
            ],
            $data
        );
        $this->assertFalse($connection->hasResponseForId(1));
    }

    public function testExceptionInvalideJson(): void
    {
        $this->expectException(CannotReadResponse::class);

        $connection = new Connection($this->mocSocket);
        $connection->connect();

        // set invalid json
        $this->mocSocket->addReceivedData('{');

        $connection->readData();
    }

    public function testExceptionInvalideArrayResponse(): void
    {
        $this->expectException(CannotReadResponse::class);

        $connection = new Connection($this->mocSocket);
        $connection->connect();

        // set string variable instead of array
        $this->mocSocket->addReceivedData('"foo"');

        $connection->readData();
    }

    public function testInvalidResponseId(): void
    {
        $this->expectException(InvalidResponse::class);

        $connection = new Connection($this->mocSocket);
        $connection->connect();

        // set string variable instead of array
        $this->mocSocket->addReceivedData('{"message": "foo"}');

        $connection->readData();
    }
}
