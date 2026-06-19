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
use HeadlessChromium\Dom\Selector\CssSelector;
use HeadlessChromium\Dom\Selector\Selector;
use HeadlessChromium\Exception\ElementNotFoundException;
use HeadlessChromium\Exception\JavascriptException;
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
    public function move(int $x, int $y, ?array $options = null)
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
    public function press(?array $options = null)
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
    public function release(?array $options = null)
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
    public function click(?array $options = null)
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
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     *
     * @return $this
     */
    public function scrollUp(int $distance)
    {
        return $this->scroll(-1 * \abs($distance));
    }

    /**
     * Scroll down using the mouse wheel.
     *
     * @param int $distance Distance in pixels
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
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

        // make sure the mouse is on the screen
        $this->move($this->x, $this->y);

        $distances = $this->getScrollDistances($distanceY, $distanceX); // Calculated distances to scroll.
        $startPos = $this->getCurrentScrollPosition(); // Remember positions before scrolling started.

        if ($distances['x'] === 0 && $distances['y'] === 0) {
            return $this;
        }

        $this->sendScrollMessage($distances);

        // Wait until the scroll position settles (i.e. it stops changing).
        Utils::tryWithTimeout(
            10_000_000,
            $this->waitForScrollToSettle($distanceY, $distanceX, $startPos['x'], $startPos['y'])
        );

        // set new position after move
        $endPos = $this->getCurrentScrollPosition();
        $this->x += $endPos['x'] - $startPos['x'];
        $this->y += $endPos['y'] - $startPos['y'];

        return $this;
    }

    /**
     * Get the distances to actually scroll on each axis, clamped to the page's
     * current scroll boundaries.
     *
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     *
     * @return array{ x: int, y: int }
     */
    private function getScrollDistances(int $distanceY, int $distanceX = 0): array
    {
        $scrollableArea = $this->page->getLayoutMetrics()->getCssContentSize();
        $visibleArea = $this->page->getLayoutMetrics()->getCssVisualViewport();

        $maximumX = $scrollableArea['width'] - $visibleArea['clientWidth'];
        $maximumY = $scrollableArea['height'] - $visibleArea['clientHeight'];

        $distanceX = $this->getMaximumDistance($distanceX, (int) $visibleArea['pageX'], (int) $maximumX);
        $distanceY = $this->getMaximumDistance($distanceY, (int) $visibleArea['pageY'], (int) $maximumY);

        return ['x' => $distanceX, 'y' => $distanceY];
    }

    private function getCurrentScrollPosition(): array
    {
        $viewport = $this->page->getLayoutMetrics()->getCssVisualViewport();

        return ['x' => (int) $viewport['pageX'], 'y' => (int) $viewport['pageY']];
    }

    /**
     * @param array{ x: int, y: int } $distances
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     */
    private function sendScrollMessage(array $distances): void
    {
        $this->page->getSession()->sendMessageSync(new Message('Input.dispatchMouseEvent', [
            'type' => 'mouseWheel',
            'x' => $this->x,
            'y' => $this->y,
            'deltaX' => $distances['x'],
            'deltaY' => $distances['y'],
        ]));
    }

    /**
     * Scroll in both X and Y axis until the given boundaries fit in the screen.
     *
     * This method currently scrolls only to right and bottom. If the desired element is outside the visible screen
     * to the left or top, thie method will not work. Its visibility will stay private until it works for both cases.
     *
     * @param int $right  The element right boundary
     * @param int $bottom The element bottom boundary
     *
     * @return $this
     */
    private function scrollToBoundary(int $right, int $bottom): self
    {
        $visibleArea = $this->page->getLayoutMetrics()->getCssLayoutViewport();

        $distanceX = $distanceY = 0;

        if ($right > $visibleArea['clientWidth']) {
            $distanceX = $right - $visibleArea['clientWidth'];
        }

        if ($bottom > $visibleArea['clientHeight']) {
            $distanceY = $bottom - $visibleArea['clientHeight'];
        }

        return $this->scroll($distanceY, $distanceX);
    }

    /**
     * Find an element and move the mouse to a random position over it.
     *
     * The search could result in several elements. The $position param can be used to select a specific element.
     * The given position can only be between 1 and the maximum number or elements. It will be adjusted to the
     * minimum and maximum values if needed.
     *
     * Example:
     * $page->mouse()->find('#a'):
     * $page->mouse()->find('.a', 2);
     *
     * @see https://developer.mozilla.org/docs/Web/API/Document/querySelector
     *
     * @param string $selectors selectors to use with document.querySelector
     * @param int    $position  (optional) which element of the result set should be used
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\ElementNotFoundException
     *
     * @return $this
     */
    public function find(string $selectors, int $position = 1): self
    {
        $this->findElement(new CssSelector($selectors), $position);

        return $this;
    }

    /**
     * Find an element and move the mouse to a random position over it.
     *
     * The search could result in several elements. The $position param can be used to select a specific element.
     * The given position can only be between 1 and the maximum number or elements. It will be adjusted to the
     * minimum and maximum values if needed.
     *
     * Example:
     * $page->mouse()->findElement(new CssSelector('#a')):
     * $page->mouse()->findElement(new CssSelector('.a'), 2);
     * $page->mouse()->findElement(new XPathSelector('//*[@id="a"]'), 2);
     *
     * @param Selector $selector selector to use
     * @param int      $position (optional) which element of the result set should be used
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\ElementNotFoundException
     *
     * @return $this
     */
    public function findElement(Selector $selector, int $position = 1): self
    {
        $this->page->assertNotClosed();

        try {
            $element = Utils::getElementPositionFromPage($this->page, $selector, $position);
        } catch (JavascriptException $exception) {
            throw new ElementNotFoundException('The search for "'.$selector->expressionCount().'" returned no result.');
        }

        if (false === \array_key_exists('x', $element)) {
            throw new ElementNotFoundException('The search for "'.$selector->expressionFindOne($position).'" returned an element with no position.');
        }

        $rightBoundary = \floor($element['right']);
        $bottomBoundary = \floor($element['bottom']);

        $this->scrollToBoundary($rightBoundary, $bottomBoundary);

        $visibleArea = $this->page->getLayoutMetrics()->getLayoutViewport();

        $offsetX = $visibleArea['pageX'];
        $offsetY = $visibleArea['pageY'];
        $minX = $element['left'] - $offsetX;
        $minY = $element['top'] - $offsetY;

        $positionX = \floor($minX + (($rightBoundary - $offsetX) - $minX) / 2);
        $positionY = \ceil($minY + (($bottomBoundary - $offsetY) - $minY) / 2);

        $this->move($positionX, $positionY);

        return $this;
    }

    /**
     * Get the maximum distance to scroll a page.
     *
     * @param int $distance Distance to scroll, positive or negative
     * @param int $current  Current position
     * @param int $maximum  Maximum possible distance
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
     * Wait for the scroll position to settle (stop changing).
     *
     * Rather than waiting for an exact target position, this polls the live
     * scroll position and returns once it has been stable for a few consecutive
     * reads. A settled position is only accepted once we have either observed
     * actual movement or reached a scroll boundary - this guards against
     * accepting the start position before the scroll has even begun
     * (the start-of-scroll race).
     *
     * Yields the number of microseconds to wait before trying again or
     * returns in case of success.
     *
     * @see Utils::tryWithTimeout
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    private function waitForScrollToSettle(int $distanceY, int $distanceX, int $startX, int $startY): \Generator
    {
        $requiredStableReads = 5;
        $stableReads = 0;
        $lastX = $lastY = null;
        $moved = false;

        while (true) {
            $pos = $this->getCurrentScrollPosition();
            $moved = $moved || ($pos['x'] !== $startX || $pos['y'] !== $startY);

            if ($pos['x'] === $lastX && $pos['y'] === $lastY) {
                ++$stableReads;

                if (
                    $stableReads >= $requiredStableReads &&
                    ($moved || $this->isAtScrollBoundary($distanceY, $distanceX, $pos['x'], $pos['y']))
                ) {
                    return;
                }
            } else {
                $stableReads = 0;
                $lastX = $pos['x'];
                $lastY = $pos['y'];
            }

            yield 16_000; // Time between two frames at 60Hz.
        }
    }

    /**
     * Whether the page can no longer be scrolled further in the requested
     * direction (so a non-moving position is a legitimate end state).
     *
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    private function isAtScrollBoundary(int $distanceY, int $distanceX, int $x, int $y): bool
    {
        $scrollableArea = $this->page->getLayoutMetrics()->getCssContentSize();
        $visibleArea = $this->page->getLayoutMetrics()->getCssVisualViewport();

        $maximumX = (int) ($scrollableArea['width'] - $visibleArea['clientWidth']);
        $maximumY = (int) ($scrollableArea['height'] - $visibleArea['clientHeight']);

        $atBoundaryX = $distanceX > 0 ? $x >= $maximumX : ($distanceX < 0 ? $x <= 0 : true);
        $atBoundaryY = $distanceY > 0 ? $y >= $maximumY : ($distanceY < 0 ? $y <= 0 : true);

        return $atBoundaryX && $atBoundaryY;
    }

    /**
     * Get the current mouse position.
     *
     * @return array [x, y]
     */
    public function getPosition(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
        ];
    }
}
