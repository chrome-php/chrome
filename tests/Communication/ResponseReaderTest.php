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
use HeadlessChromium\Communication\Socket\MockSocket;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\OperationTimedOut;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HeadlessChromium\Communication\ResponseReader
 */
class ResponseReaderTest extends TestCase
{
    public function testMessage()
    {
        $message = new Message('foo', ['bar' => 'baz']);
        $mockSocket = new MockSocket();
        $connection = new Connection($mockSocket);

        $responseReader = new ResponseReader($message, $connection);

        $this->assertSame($message, $responseReader->getMessage());
        $this->assertSame($connection, $responseReader->getConnection());

        // no response
        $this->assertFalse($responseReader->hasResponse());

        try {
            $responseReader->waitForResponse(1);
            $this->fail('exception not thrown');
        } catch (OperationTimedOut $e) {
            $this->assertTrue(true);
        }

        $this->assertFalse($responseReader->checkForResponse());

        // add response
        $mockSocket->addReceivedData(json_encode(['id' => $message->getId(), 'foo' => 'qux']));

        $this->assertTrue($responseReader->checkForResponse());
        $this->assertTrue($responseReader->hasResponse());
        $this->assertInstanceOf(Response::class, $responseReader->getResponse());
        $this->assertSame($responseReader->waitForResponse(1), $responseReader->getResponse());

        $this->assertEquals(['id' => $message->getId(), 'foo' => 'qux'], $responseReader->getResponse()->getData());
    }

    public function testWaitForResponse()
    {
        $message = new Message('foo', ['bar' => 'baz']);
        $mockSocket = new MockSocket();
        $connection = new Connection($mockSocket);

        $responseReader = new ResponseReader($message, $connection);

        try {
            $responseReader->waitForResponse(1);
            $this->fail('exception not thrown');
        } catch (OperationTimedOut $e) {
            $this->assertTrue(true);
        }

        // receive data
        $mockSocket->addReceivedData(json_encode(['id' => $message->getId(), 'foo' => 'qux']));

        // timeout should not be reached and response should be get immediately
        $response = $responseReader->waitForResponse(0);
        $this->assertInstanceOf(Response::class, $response);

        // response should be stored
        $this->assertTrue($responseReader->hasResponse());
        $this->assertSame($response, $responseReader->getResponse());
        $this->assertSame($response, $responseReader->waitForResponse(0));
    }

    /**
     * Tests that waitForResponse will stop dispatching data once it got the response for its message.
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
     */
    public function testWaitForResponseIsAtomic()
    {
        $message = new Message('foo', ['bar' => 'baz']);
        $mockSocket = new MockSocket();
        $connection = new Connection($mockSocket);

        $emitWatcher = new \stdClass();
        $emitWatcher->emittedCount = 0;

        $connection->on('method:qux.quux', function () use ($emitWatcher) {
            $emitWatcher->emittedCount++;
        });

        $responseReader = new ResponseReader($message, $connection);

        // receive data
        $mockSocket->addReceivedData(json_encode(['id' => $message->getId(), 'foo' => 'qux']));
        $mockSocket->addReceivedData(json_encode(['method' => 'qux.quux', 'params' => []]));

        // wait for response should not read the second message (method:qux.quux)
        $response = $responseReader->waitForResponse(1);
        $this->assertEquals(['id' => $message->getId(), 'foo' => 'qux'], $response->getData());
        $this->assertEquals(0, $emitWatcher->emittedCount);

        // next call to read line should read the second message (method:qux.quux)
        $connection->readLine();
        $this->assertEquals(1, $emitWatcher->emittedCount);
    }

    public function testExceptionNoResponse()
    {
        $message = new Message('foo', ['bar' => 'baz']);
        $mockSocket = new MockSocket();
        $connection = new Connection($mockSocket);
        $responseReader = new ResponseReader($message, $connection);

        $this->expectException(NoResponseAvailable::class);

        $responseReader->getResponse();
    }
}
