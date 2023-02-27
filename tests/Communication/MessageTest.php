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
    public function testMessage(): void
    {
        $message = new Message('foo', ['bar' => 'baz']);
        self::assertEquals(Message::getLastMessageId(), $message->getId());
        self::assertEquals('foo', $message->getMethod());
        self::assertEquals(['bar' => 'baz'], $message->getParams());

        self::assertEquals(
            \json_encode(['id' => $message->getId(), 'method' => 'foo', 'params' => ['bar' => 'baz']]),
            (string) $message
        );

        $message2 = new Message('qux', ['quux' => 'corge']);
        self::assertEquals(Message::getLastMessageId(), $message2->getId());
        self::assertNotSame($message->getId(), $message2->getId());
        self::assertEquals('qux', $message2->getMethod());
        self::assertEquals(['quux' => 'corge'], $message2->getParams());
    }
}
