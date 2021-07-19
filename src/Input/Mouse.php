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
use HeadlessChromium\Utils;

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
     * @param int        $x
     * @param int        $y
     * @param array|null $options
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     *
     * @return $this
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
        for ($i = 1; $i <= $steps; ++$i) {
            $this->page->getSession()->sendMessageSync(new Message('Input.dispatchMouseEvent', [
                'x' => $originX + ($this->x - $originX) * ($i / $steps),
                'y' => $originY + ($this->y - $originY) * ($i / $steps),
                'type' => 'mouseMoved',
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
            'clickCount' => 1,
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
            'clickCount' => 1,
        ]));

        return $this;
    }

    /**
     * @param array|null $options
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    public function click(array $options = null)
    {
        $this->press($options);
        $this->release($options);

        return $this;
    }

    /**
     * Scroll up using the mouse wheel.
     *
     * @param int $distance Distance in pixels
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     *
     * @return $this
     */
    public function scrollUp(int $distance)
    {
        return $this->scroll((-1 * \abs($distance)));
    }

    /**
     * Scroll down using the mouse wheel.
     *
     * @param int $distance Distance in pixels
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     *
     * @return $this
     */
    public function scrollDown(int $distance)
    {
        return $this->scroll(\abs($distance));
    }

    /**
     * Scroll a positive or negative distance using the mouseWheel event type.
     *
     * @param int $distanceY Distance in pixels for the Y axis
     * @param int $distanceX (optional) Distance in pixels for the X axis
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return $this
     */
    private function scroll(int $distanceY, int $distanceX = 0): self
    {
        $this->page->assertNotClosed();

        $scollableArea = $this->page->getLayoutMetrics()->getContentSize();
        $visibleArea = $this->page->getLayoutMetrics()->getVisualViewport();

        $distanceX = $this->getMaximumDistance($distanceX, $visibleArea['pageX'], $scollableArea['height']);
        $distanceY = $this->getMaximumDistance($distanceY, $visibleArea['pageY'], $scollableArea['width']);

        $targetX = $visibleArea['pageX'] + $distanceX;
        $targetY = $visibleArea['pageY'] + $distanceY;

        // make sure the mouse is on the screen
        $this->move($this->x, $this->y);

        // scroll
        $this->page->getSession()->sendMessageSync(new Message('Input.dispatchMouseEvent', [
            'type' => 'mouseWheel',
            'x' => $this->x,
            'y' => $this->y,
            'deltaX' => $distanceX,
            'deltaY' => $distanceY,
        ]));

        // wait until the scroll is done
        Utils::tryWithTimeout(30000 * 1000, $this->waitForScroll($targetX, $targetY));

        // set new position after move
        $this->x += $distanceX;
        $this->y += $distanceY;

        return $this;
    }

    /**
     * Get the maximum distance to scroll a page.
     *
     * @param int $distance Distance to scroll, positive or negative
     * @param int $current  Current position
     * @param int $maximum  Maximum posible distance
     *
     * @return int allowed distance to scroll
     */
    private function getMaximumDistance(int $distance, int $current, int $maximum): int
    {
        $result = $current + $distance;

        if ($result < 0) {
            return $distance + \abs($result);
        }

        if ($result > $maximum) {
            return $maximum - $current;
        }

        return $distance;
    }

    /**
     * Wait for the browser to process the scroll command.
     *
     * Return the number of microseconds to wait before trying again or true in case of success.
     *
     * @see \HeadlessChromium\Utils::tryWithTimeout
     *
     * @param int $targetX
     * @param int $targetY
     *
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return bool|\Generator
     */
    private function waitForScroll(int $targetX, int $targetY)
    {
        while (true) {
            $visibleArea = $this->page->getLayoutMetrics()->getVisualViewport();

            if ($visibleArea['pageX'] === $targetX && $visibleArea['pageY'] === $targetY) {
                return true;
            }

            yield 1000;
        }
    }
}
