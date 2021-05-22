<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Test\Communication\Socket;

use HeadlessChromium\Communication\Socket\MockSocket;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HeadlessChromium\Communication\Socket\MockSocket
 */
class MockSocketTest extends TestCase
{
    public function testMockSocket(): void
    {
        $mock = new MockSocket();

        // not connected
        $this->assertFalse($mock->isConnected());
        $this->assertFalse($mock->sendData('foo'));
        $this->assertEmpty($mock->getSentData());
        $this->assertEmpty($mock->receiveData());

        // connected
        $mock->connect();

        $this->assertTrue($mock->isConnected());
        $this->assertTrue($mock->sendData('foo'));
        $this->assertEquals(['foo'], $mock->getSentData());
        $this->assertEquals(['foo'], $mock->getSentData()); // not empty until flush
        $this->assertEmpty($mock->receiveData());

        // flush sent data
        $mock->flushData();
        $this->assertEmpty($mock->getSentData());

        // with received data
        $mock->addReceivedData('bar');

        $this->assertEquals(['bar'], $mock->receiveData());
        $this->assertEmpty($mock->receiveData());

        // disconnected
        $mock->disconnect();
        $this->assertFalse($mock->isConnected());
    }

    public function testReceivedDateForNextMessage(): void
    {
        $mock = new MockSocket();

        // connected
        $mock->connect();

        $mock->addReceivedData(\json_encode(['foo' => 'bar']), true);

        $this->assertEmpty($mock->receiveData());

        $mock->sendData(\json_encode(['id' => 1]));

        $this->assertEquals([\json_encode(['foo' => 'bar', 'id' => 1])], $mock->receiveData());
    }
}
