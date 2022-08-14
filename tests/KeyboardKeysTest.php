<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Test;

use HeadlessChromium\Input\Keys;

/**
 * @covers \HeadlessChromium\Input\KeyboardKeys
 */
class KeyboardKeysTest extends BaseTestCase
{
    /**
     * @var KeyboardForTests
     */
    private $keyboard;

    protected function setUp(): void
    {
        $this->keyboard = new KeyboardForTests();
    }

    public function keyProvider(): array
    {
        return [
            // Key,   expectedKey
            ['a',     'A'],
            ['A',     'A'],
            ['key',   'Key'],
            [' KEY ', 'KEY'],
        ];
    }

    public function modifierKeyProvider(): array
    {
        return [
            // Key,     expectedModifier
            ['Alt',     1],
            ['AltGr',   1],
            ['Alt Gr',  1],

            ['Control', 2],
            ['ctrl',    2],
            ['Ctr',     2],

            ['Meta',    4],
            ['Cmd',     4],
            ['Command', 4],

            ['Shift',   8],

            [Keys::Alt,     1],
            [Keys::Control, 2],
            [Keys::Meta,    4],
            [Keys::Command, 4],
            [Keys::Shift,   8],
        ];
    }

    public function keyCodesProvider(): array
    {
        return [
            ['a', 65],
            ['A', 65],
        ];
    }

    /**
     * @dataProvider keyProvider
     */
    public function testOnKeyPressAndRelease(string $key, string $expectedKey): void
    {
        $this->assertFalse($this->keyboard->isKeyPressed());
        $this->assertEquals(0, $this->keyboard->getModifiers());

        $this->keyboard->onKeyPress($key);

        $this->assertEquals($expectedKey, $this->keyboard->getCurrentKey());
        $this->assertEquals(0, $this->keyboard->getModifiers());
        $this->assertEquals(1, \count($this->keyboard->getPressedKeys()));
        $this->assertTrue($this->keyboard->isKeyPressed());

        $this->keyboard->onKeyRelease($key);

        $this->assertEquals($expectedKey, $this->keyboard->getCurrentKey());
        $this->assertEquals(0, \count($this->keyboard->getPressedKeys()));
        $this->assertEquals(0, $this->keyboard->getModifiers());
        $this->assertFalse($this->keyboard->isKeyPressed());
    }

    /**
     * @dataProvider modifierKeyProvider
     */
    public function testToggleModifierFromKey($key, $expectedModifier): void
    {
        $this->keyboard->setCurrentKey($key);
        $this->keyboard->toggleModifierFromKey();

        $this->keyboard->setCurrentKey('NonModifierKey');
        $this->keyboard->toggleModifierFromKey();

        $this->assertEquals($expectedModifier, $this->keyboard->getModifiers());

        $this->keyboard->setCurrentKey($key);
        $this->keyboard->toggleModifierFromKey();
        $this->assertEquals(0, $this->keyboard->getModifiers());
    }

    public function testToggleModifier(): void
    {
        $this->keyboard->toggleModifier(0b0001);
        $this->keyboard->toggleModifier(0b0010);

        $this->assertEquals(0b0011, $this->keyboard->getModifiers());

        $this->keyboard->toggleModifier(0b0010);

        $this->assertEquals(0b0001, $this->keyboard->getModifiers());
    }

    /**
     * @dataProvider keyCodesProvider
     */
    public function testGetKeyCode(string $key, int $code): void
    {
        $this->keyboard->setCurrentKey($key);

        $this->assertEquals($code, $this->keyboard->getKeyCode());
    }
}
