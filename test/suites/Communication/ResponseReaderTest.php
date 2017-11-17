<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Test\Communication;

use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\Response;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Communication\Socket\MockSocket;
use HeadlessChromium\Exception\NoResponseAvailable;
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
        $this->assertNull($responseReader->waitForResponse(1));
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

        $this->assertNull($responseReader->waitForResponse(1));

        // test that timeout is respected
        $this->assertEquals(null, $this->timeWaitForResponse($responseReader, 10, false));

        // receive data
        $mockSocket->addReceivedData(json_encode(['id' => $message->getId(), 'foo' => 'qux']));

        // timeout should not be reached and response should be get immediately
        $response = $this->timeWaitForResponse($responseReader, 20, true);
        $this->assertInstanceOf(Response::class, $response);

        // response should be stored
        $this->assertTrue($responseReader->hasResponse());
        $this->assertSame($response, $responseReader->getResponse());
        $this->assertSame($response, $responseReader->waitForResponse(0));
    }

    private function timeWaitForResponse(ResponseReader $responseReader, $timeout, $immediate, $delta = 8)
    {
        $a = microtime(true);

        $r = $responseReader->waitForResponse($timeout);

        $duration = microtime(true) - $a;

        if ($immediate) {
            // if $immediate (has already a response in the buffer) it should be fast (i.e less that 0.1ms)
            $this->assertLessThan(0.1, $duration * 1000);
        } else {
            $this->assertLessThan($timeout + $delta, 1000 * $duration);
            $this->assertGreaterThan($timeout - $delta, 1000 * $duration);
        }

        return $r;
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
