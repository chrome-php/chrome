<?php
/**
 * @license see LICENSE
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

    public function testMessage()
    {
        $message = new Message('foo', ['bar' => 'baz']);
        $response = new Response(['id' => $message->getId(), 'bar' => 'foo'], $message);

        $this->assertSame($message, $response->getMessage());
        $this->assertTrue($response->isSuccessful());
        $this->assertTrue(isset($response['bar']));
        $this->assertEquals('foo', $response['bar']);
        $this->assertEquals(['id' => $message->getId(), 'bar' => 'foo'], $response->getData());
    }

    public function testIsNotSuccessful()
    {
        $message = new Message('foo', ['bar' => 'baz']);
        $response = new Response(['id' => $message->getId(), 'error' => 'foo'], $message);

        $this->assertFalse($response->isSuccessful());
    }
}
