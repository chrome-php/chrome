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

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Page;

class Keyboard
{
    /**
     * @var Page
     */
    protected $page;

    /**
     * @var int
     */
    protected $sleep = 0;

    /**
     * @param Page $page
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    /**
     * Type a text string, char by char
     *
     * @return $this
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function typeText(string $text)
    {
        $this->page->assertNotClosed();

        $length = strlen($text);

        // apparently the first character doesn't work
        $this->page->getSession()->sendMessageSync(new Message('Input.dispatchKeyEvent', [
            'type' => 'char',
            'text' => '',
        ]));

        for ($i = 0; $i < $length; $i++) {
            $this->page->getSession()->sendMessageSync(new Message('Input.dispatchKeyEvent', [
                'type' => 'char',
                'text' => $text[$i],
            ]));

            usleep($this->sleep);
        }

        return $this;
    }

    /**
     * Type a single raw key wich rawKeyDown
     *
     * Example:
     *
     * ```php
     * $page->keyboard()->typeRawKey('Tab');
     * ```
     *
     * @return $this
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function typeRawKey(string $key)
    {
        $this->page->assertNotClosed();

        $this->page->getSession()->sendMessageSync(new Message('Input.dispatchKeyEvent', [
            'type' => 'rawKeyDown',
            'key' => $key,
        ]));

        usleep($this->sleep);

        return $this;
    }

    /**
     * Sets the time interval between key strokes in milliseconds
     *
     * @param int $milliseconds
     *
     * @return $this
     */
    public function setKeyInterval(int $milliseconds)
    {
        if ($milliseconds < 0) {
            $milliseconds = 0;
        }

        $this->sleep = $milliseconds * 1000;

        return $this;
    }
}
