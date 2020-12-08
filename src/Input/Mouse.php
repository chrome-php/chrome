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

class Mouse
{
    public const BUTTON_LEFT = 'left';
    public const BUTTON_NONE = 'none';
    public const BUTTON_RIGHT = 'right';
    public const BUTTON_MIDDLE = 'middle';

    /**
     * @var Page
     */
    protected $page;

    protected $x = 0;
    protected $y = 0;

    protected $button = self::BUTTON_NONE;

    /**
     * @param Page $page
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    /**
     * @param int $x
     * @param int $y
     * @param array|null $options
     * @return $this
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function move(int $x, int $y, array $options = null)
    {
        $this->page->assertNotClosed();

        // get origin of the move
        $originX = $this->x;
        $originY = $this->y;

        // set new position after move
        $this->x = $x;
        $this->y = $y;

        // number of steps to achieve the move
        $steps = $options['steps'] ?? 1;
        if ($steps <= 0) {
            throw new \InvalidArgumentException('options "steps" for mouse move must be a positive integer');
        }

        // move
        for ($i = 1; $i <= $steps; $i++) {
            $this->page->getSession()->sendMessageSync(new Message('Input.dispatchMouseEvent', [
                'x' => $originX + ($this->x - $originX) * ($i / $steps),
                'y' => $originY + ($this->y - $originY) * ($i / $steps),
                'type' => 'mouseMoved'
            ]));
        }

        return $this;
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function press(array $options = null)
    {
        $this->page->assertNotClosed();
        $this->page->getSession()->sendMessageSync(new Message('Input.dispatchMouseEvent', [
            'x' => $this->x,
            'y' => $this->y,
            'type' => 'mousePressed',
            'button' => $options['button'] ?? self::BUTTON_LEFT,
            'clickCount' => 1
        ]));

        return $this;
    }

    /**
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function release(array $options = null)
    {
        $this->page->assertNotClosed();
        $this->page->getSession()->sendMessageSync(new Message('Input.dispatchMouseEvent', [
            'x' => $this->x,
            'y' => $this->y,
            'type' => 'mouseReleased',
            'button' => $options['button'] ?? self::BUTTON_LEFT,
            'clickCount' => 1
        ]));

        return $this;
    }

    /**
     * @param array|null $options
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function click(array $options = null)
    {
        $this->press($options);
        $this->release($options);

        return $this;
    }
}
