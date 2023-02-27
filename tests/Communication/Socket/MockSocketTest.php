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
        self::assertFalse($mock->isConnected());
        self::assertFalse($mock->sendData('foo'));
        self::assertEmpty($mock->getSentData());
        self::assertEmpty($mock->receiveData());

        // connected
        $mock->connect();

        self::assertTrue($mock->isConnected());
        self::assertTrue($mock->sendData('foo'));
        self::assertEquals(['foo'], $mock->getSentData());
        self::assertEquals(['foo'], $mock->getSentData()); // not empty until flush
        self::assertEmpty($mock->receiveData());

        // flush sent data
        $mock->flushData();
        self::assertEmpty($mock->getSentData());

        // with received data
        $mock->addReceivedData('bar');

        self::assertEquals(['bar'], $mock->receiveData());
        self::assertEmpty($mock->receiveData());

        // disconnected
        $mock->disconnect();
        self::assertFalse($mock->isConnected());
    }

    public function testReceivedDateForNextMessage(): void
    {
        $mock = new MockSocket();

        // connected
        $mock->connect();

        $mock->addReceivedData(\json_encode(['foo' => 'bar']), true);

        self::assertEmpty($mock->receiveData());

        $mock->sendData(\json_encode(['id' => 1]));

        self::assertEquals([\json_encode(['foo' => 'bar', 'id' => 1])], $mock->receiveData());
    }
}
