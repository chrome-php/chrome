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

use HeadlessChromium\Communication\Message;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HeadlessChromium\Communication\Message
 */
class MessageTest extends TestCase
{
    public function testMessage()
    {
        $message = new Message('foo', ['bar' => 'baz']);
        $this->assertEquals(Message::getLastMessageId(), $message->getId());
        $this->assertEquals('foo', $message->getMethod());
        $this->assertEquals(['bar' => 'baz'], $message->getParams());

        $this->assertEquals(
            json_encode(['id' => $message->getId(), 'method' => 'foo', 'params' => ['bar' => 'baz']]),
            (string) $message
        );

        $message2 = new Message('qux', ['quux' => 'corge']);
        $this->assertEquals(Message::getLastMessageId(), $message2->getId());
        $this->assertNotSame($message->getId(), $message2->getId());
        $this->assertEquals('qux', $message2->getMethod());
        $this->assertEquals(['quux' => 'corge'], $message2->getParams());
    }
}
