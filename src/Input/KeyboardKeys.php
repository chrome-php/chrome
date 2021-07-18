<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Input;

/**
 * Translates typed keys to their respective codes.
 *
 * @see https://chromedevtools.github.io/devtools-protocol/1-2/Input/
 */
trait KeyboardKeys
{
    /**
     * Array of currently pressed keys (keyDown events).
     *
     * The elements of this array should be unique. A real keyboard can create several keyDown events
     * by holding down a key, but only one keyUp event will be sent when the key is released.
     */
    protected $pressedKeys = [];

    /**
     * Current key as a sanitized string.
     *
     * Single letters like "v" must be in uppercase, otherwise key combinations like ctrl + v won't work.
     */
    protected $currentKey = '';

    /**
     * Bit field representing pressed modifier keys.
     */
    protected $modifiers = 0;

    /**
     * Aliases for modifier keys, in lowercase.
     */
    protected $keyAliases = [
        Key::ALT => [
            'alt',
            'altgr',
            'alt gr',
        ],
        Key::CONTROL => [
            'control',
            'ctrl',
            'ctr',
        ],
        Key::META => [
            'meta',
            'command',
            'cmd',
        ],
        Key::SHIFT => [
            'shift',
        ],
    ];

    /**
     * Register a pressed key and apply modifiers.
     *
     * @param string $key pressed key
     *
     * @return void
     */
    protected function onKeyPress(string $key): void
    {
        $this->setCurrentKey($key);

        if (true === $this->isKeyPressed()) {
            return;
        }

        $this->pressedKeys[$this->currentKey] = true;

        $this->toggleModifierFromKey();
    }

    /**
     * Register a released key and remove modifiers.
     *
     * @param string $key released key
     *
     * @return void
     */
    protected function onKeyRelease(string $key): void
    {
        $this->setCurrentKey($key);

        if (false === $this->isKeyPressed()) {
            return;
        }

        unset($this->pressedKeys[$this->currentKey]);

        $this->toggleModifierFromKey();
    }

    /**
     * Check the current key against the list of aliases.
     * If it match, try to add or remove its bits to the modifier.
     *
     * @see self::$keyAliases
     * @see self::$modifiers
     *
     * @return void
     */
    protected function toggleModifierFromKey(): void
    {
        $key = \strtolower($this->currentKey);

        foreach ($this->keyAliases as $modifier => $aliases) {
            if (true === \in_array($key, $aliases)) {
                $this->toggleModifier($modifier);
                break;
            }
        }
    }

    /**
     * Perform bit operations to add or remove bits from the modifier.
     *
     * Examples:
     *
     *   0001
     * | 0100
     * = 0101
     *
     *   0101
     * & 0100
     * = 0100
     *
     *   0101
     * & 0010
     * = 0000
     *
     * @see self::$modifiers
     *
     * @return void
     */
    protected function toggleModifier(int $bit): void
    {
        if (($this->modifiers & $bit) === $bit) {
            $this->modifiers &= ~$bit;

            return;
        }

        $this->modifiers |= $bit;
    }

    /**
     * Check if the current key was pressed and not released yet.
     *
     * @return bool true if they key is listed as pressed
     */
    protected function isKeyPressed(): bool
    {
        return \array_key_exists($this->currentKey, $this->pressedKeys);
    }

    /**
     * Return the current key code.
     *
     * @return int the key code
     */
    public function getKeyCode(): int
    {
        return \ord($this->currentKey);
    }

    /**
     * Return the current bit modifier.
     * The browser expects to receive this value as int.
     *
     * @return int current bit modifier
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    /**
     * Return the current key being processed.
     *
     * @return string the current key
     */
    public function getCurrentKey(): string
    {
        return $this->currentKey;
    }

    /**
     * Return the list of unique pressed keys that were not released yet.
     *
     * @return array list of pressed keys
     */
    public function getPressedKeys(): array
    {
        return $this->pressedKeys;
    }

    /**
     * Set a key as the current key.
     *
     * Single character keys must be in uppercase, otherwhie things like ctrl + v won't work.
     * Triming the string will also prevent future mistakes during normal usage.
     *
     * @param string $key key to be set as current
     *
     * @return void
     */
    protected function setCurrentKey(string $key): void
    {
        $this->currentKey = \ucfirst(\trim($key));
    }
}
