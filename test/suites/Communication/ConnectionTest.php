<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test\Communication;

use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Communication\Socket\MockSocket;
use HeadlessChromium\Exception\CommunicationException\CannotReadResponse;
use HeadlessChromium\Exception\CommunicationException\InvalidResponse;
use HeadlessChromium\Exception\NoResponseAvailable;
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

    public function setUp()
    {
        parent::setUp();
        $this->mocSocket = new MockSocket();
    }

    public function testIsStrict()
    {
        $connection = new Connection($this->mocSocket);
        $this->assertTrue($connection->isStrict());
        $connection->setStrict(false);
        $this->assertFalse($connection->isStrict());
    }

    public function testConnectDisconnect()
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();

        $this->assertTrue($this->mocSocket->isConnected());

        $connection->disconnect();

        $this->assertFalse($this->mocSocket->isConnected());
    }

    public function testSendMessage()
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
                json_encode([
                    'id' => $message->getId(),
                    'method' => 'foo',
                    'params' => ['bar' => 'baz']
                ])
            ],
            $this->mocSocket->getSentData()
        );
    }

    public function testSendMessageSync()
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();

        $message = new Message('foo', ['bar' => 'baz']);

        $this->mocSocket->addReceivedData(json_encode(['id' => 2, 'bar' => 'foo']));

        $response = $connection->sendMessageSync($message, 2);

        $this->assertSame($message, $response->getMessage());
        $this->assertEquals(['id' => 2, 'bar' => 'foo'], $response->getData());
    }

    public function testSendMessageSyncException()
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();

        $message = new Message('foo', ['bar' => 'baz']);

        $this->expectException(NoResponseAvailable::class);

        $connection->sendMessageSync($message, 2);
    }
    
    public function testReadData()
    {
        $connection = new Connection($this->mocSocket);
        $connection->connect();

        $this->assertFalse($connection->readData());

        $this->mocSocket->addReceivedData(json_encode([
            'id'  => 1,
            'foo' => 'bar'
        ]));

        $this->assertTrue($connection->readData());
        $this->assertFalse($connection->readData());

        $this->assertTrue($connection->hasResponseForId(1));
        $this->assertTrue($connection->hasResponseForId(1)); // still true until read
        $this->assertFalse($connection->hasResponseForId(2));

        $data = $connection->getResponseForId(1);
        $this->assertEquals(
            [
                'id'  => 1,
                'foo' => 'bar'
            ],
            $data
        );
        $this->assertFalse($connection->hasResponseForId(1));
    }

    public function testExceptionInvalideJson()
    {
        $this->expectException(CannotReadResponse::class);

        $connection = new Connection($this->mocSocket);
        $connection->connect();

        //set invalid json
        $this->mocSocket->addReceivedData('{');

        $connection->readData();
    }

    public function testExceptionInvalideArrayResponse()
    {
        $this->expectException(CannotReadResponse::class);

        $connection = new Connection($this->mocSocket);
        $connection->connect();

        //set string variable instead of array
        $this->mocSocket->addReceivedData('"foo"');

        $connection->readData();
    }

    public function testInvalidResponseId()
    {
        $this->expectException(InvalidResponse::class);

        $connection = new Connection($this->mocSocket);
        $connection->connect();

        //set string variable instead of array
        $this->mocSocket->addReceivedData('{"message": "foo"}');

        $connection->readData();
    }
}
