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
use HeadlessChromium\Communication\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \HeadlessChromium\Communication\Response
 */
class ResponseTest extends TestCase
{
    public function testMessage(): void
    {
        $message = new Message('foo', ['bar' => 'baz']);
        $response = new Response(['id' => $message->getId(), 'bar' => 'foo'], $message);

        self::assertSame($message, $response->getMessage());
        self::assertTrue($response->isSuccessful());
        self::assertTrue(isset($response['bar']));
        self::assertEquals('foo', $response['bar']);
        self::assertEquals(['id' => $message->getId(), 'bar' => 'foo'], $response->getData());
    }

    public function testIsNotSuccessful(): void
    {
        $message = new Message('foo', ['bar' => 'baz']);
        $response = new Response(['id' => $message->getId(), 'error' => 'foo'], $message);

        self::assertFalse($response->isSuccessful());
    }
}
