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
use HeadlessChromium\Communication\Response;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Communication\Session;
use HeadlessChromium\Communication\Socket\MockSocket;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HeadlessChromium\Communication\Session
 */
class SessionTest extends TestCase
{
    /**
     * @var MockSocket
     */
    protected $mockSocket;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockSocket = new MockSocket();
    }

    public function testSession(): void
    {
        $connection = new Connection($this->mockSocket);
        $session = new Session('foo', 'bar', $connection);

        self::assertEquals('foo', $session->getTargetId());
        self::assertEquals('bar', $session->getSessionId());
        self::assertSame($connection, $session->getConnection());
    }

    public function testSendMessage(): void
    {
        $connection = new Connection($this->mockSocket);
        $connection->connect();
        $session = new Session('foo', 'bar', $connection);

        $message = new Message('baz', ['qux' => 'quux']);

        $responseReader = $session->sendMessage($message);

        self::assertInstanceOf(ResponseReader::class, $responseReader);
        self::assertEquals(
            [
                \json_encode([
                    'id' => $message->getId(),
                    'method' => 'baz',
                    'params' => ['qux' => 'quux'],
                    'sessionId' => 'bar',
                ]),
            ],
            $this->mockSocket->getSentData()
        );
    }

    public function testSendMessageSync(): void
    {
        $connection = new Connection($this->mockSocket);
        $connection->connect();
        $session = new Session('foo', 'bar', $connection);

        $message = new Message('baz', ['qux' => 'quux']);

        $this->mockSocket->addReceivedData(\json_encode(['corge' => 'grault']), true);
        $this->mockSocket->addReceivedData(\json_encode(['id' => $message->getId(), 'garply' => 'thud']));

        $response = $session->sendMessageSync($message);

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(
            [
                \json_encode([
                    'id' => $message->getId(),
                    'method' => 'baz',
                    'params' => ['qux' => 'quux'],
                    'sessionId' => 'bar',
                ]),
            ],
            $this->mockSocket->getSentData()
        );
    }
}
