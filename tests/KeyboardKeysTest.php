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

/**
 * @covers \HeadlessChromium\Input\KeyboardKeys
 */
class KeyboardKeysTest extends BaseTestCase
{
    private KeyboardForTests $keyboard;

    protected function setUp(): void
    {
        $this->keyboard = new KeyboardForTests();
    }

    public static function keyProvider(): array
    {
        return [
            // Key,   expectedKey
            ['a',     'A'],
            ['A',     'A'],
            ['key',   'Key'],
            [' KEY ', 'KEY'],
        ];
    }

    public static function modifierKeyProvider(): array
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
        ];
    }

    public static function keyCodesProvider(): array
    {
        return [
            ['a', 65],
            ['A', 65],
        ];
    }

    /**
     * @dataProvider keyProvider
     */
    public function testOnKeyPressAndRelease($key, $expectedKey): void
    {
        self::assertFalse($this->keyboard->isKeyPressed());
        self::assertEquals(0, $this->keyboard->getModifiers());

        $this->keyboard->onKeyPress($key);

        self::assertEquals($expectedKey, $this->keyboard->getCurrentKey());
        self::assertEquals(0, $this->keyboard->getModifiers());
        self::assertEquals(1, \count($this->keyboard->getPressedKeys()));
        self::assertTrue($this->keyboard->isKeyPressed());

        $this->keyboard->onKeyRelease($key);

        self::assertEquals($expectedKey, $this->keyboard->getCurrentKey());
        self::assertEquals(0, \count($this->keyboard->getPressedKeys()));
        self::assertEquals(0, $this->keyboard->getModifiers());
        self::assertFalse($this->keyboard->isKeyPressed());
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

        self::assertEquals($expectedModifier, $this->keyboard->getModifiers());

        $this->keyboard->setCurrentKey($key);
        $this->keyboard->toggleModifierFromKey();
        self::assertEquals(0, $this->keyboard->getModifiers());
    }

    public function testToggleModifier(): void
    {
        $this->keyboard->toggleModifier(0b0001);
        $this->keyboard->toggleModifier(0b0010);

        self::assertEquals(0b0011, $this->keyboard->getModifiers());

        $this->keyboard->toggleModifier(0b0010);

        self::assertEquals(0b0001, $this->keyboard->getModifiers());
    }

    /**
     * @dataProvider keyCodesProvider
     */
    public function testGetKeyCode($key, $code): void
    {
        $this->keyboard->setCurrentKey($key);

        self::assertEquals($code, $this->keyboard->getKeyCode());
    }
}
