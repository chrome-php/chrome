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

use Generator;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Dom\Selector\CssSelector;
use HeadlessChromium\Dom\Selector\Selector;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\CommunicationException\ResponseHasError;
use HeadlessChromium\Exception\ElementNotFoundException;
use HeadlessChromium\Exception\JavascriptException;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Page;
use HeadlessChromium\Utils;
use InvalidArgumentException;

class Mouse
{
    public const BUTTON_LEFT = 'left';
    public const BUTTON_NONE = 'none';
    public const BUTTON_RIGHT = 'right';
    public const BUTTON_MIDDLE = 'middle';

    private const SCROLL_TIMEOUT = 10_000_000;
    private const SCROLL_POLL_INTERVAL = 16_000; // one frame at 60Hz, in microseconds
    private const SCROLL_SETTLE_READS = 5;
    private const SCROLL_UNMOVED_SETTLE_READS = 15; // covers compositor-to-main commit latency when nothing scrolls

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
     * @throws CommunicationException
     * @throws NoResponseAvailable
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
            throw new InvalidArgumentException('options "steps" for mouse move must be a positive integer');
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
     * @throws CommunicationException
     * @throws NoResponseAvailable
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
     * @throws CommunicationException
     * @throws NoResponseAvailable
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
     * @throws CommunicationException
     * @throws NoResponseAvailable
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
     * @throws CommunicationException
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
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
     * @throws CommunicationException
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
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
     * The requested distance is clamped to the current scroll boundaries of the page. The method
     * returns once the scroll position has settled, which is not necessarily at the requested
     * distance: the page may shrink, grow, lock scrolling, or consume the wheel event while the
     * scroll is in flight.
     *
     * @param int $distanceY Distance in pixels for the Y axis
     * @param int $distanceX (optional) Distance in pixels for the X axis
     *
     * @throws CommunicationException
     * @throws ResponseHasError
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
     *
     * @return $this
     */
    private function scroll(int $distanceY, int $distanceX = 0): self
    {
        $this->page->assertNotClosed();

        $metrics = $this->page->getLayoutMetrics();
        $scrollableArea = $metrics->getCssContentSize();
        $visibleArea = $metrics->getCssVisualViewport();

        // the protocol reports doubles that may be fractional (zoom, device pixel ratio)
        $startX = (int) $visibleArea['pageX'];
        $startY = (int) $visibleArea['pageY'];

        // scrollbar asymmetries can push the raw difference slightly negative
        $maximumX = \max(0, (int) ($scrollableArea['width'] - $visibleArea['clientWidth']));
        $maximumY = \max(0, (int) ($scrollableArea['height'] - $visibleArea['clientHeight']));

        $distanceX = $this->getMaximumDistance($distanceX, $startX, $maximumX);
        $distanceY = $this->getMaximumDistance($distanceY, $startY, $maximumY);

        if (0 === $distanceX && 0 === $distanceY) {
            return $this;
        }

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
        Utils::tryWithTimeout(self::SCROLL_TIMEOUT, $this->waitForScrollToSettle($startX, $startY, $startX + $distanceX, $startY + $distanceY));

        // set new position after move
        $endPosition = $this->getCurrentScrollPosition();
        $this->x += $endPosition['x'] - $startX;
        $this->y += $endPosition['y'] - $startY;

        return $this;
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
     * @throws CommunicationException
     * @throws NoResponseAvailable
     * @throws ElementNotFoundException
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
     * @throws CommunicationException
     * @throws NoResponseAvailable
     * @throws ElementNotFoundException
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
     * Get the current page scroll position in CSS pixels.
     *
     * The protocol reports doubles that may be fractional (zoom, device pixel ratio), so the
     * values are truncated to allow positions to be compared for equality.
     *
     * @throws CommunicationException
     * @throws ResponseHasError
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
     *
     * @return array{x: int, y: int}
     */
    private function getCurrentScrollPosition(): array
    {
        $viewport = $this->page->getLayoutMetrics()->getCssVisualViewport();

        return ['x' => (int) $viewport['pageX'], 'y' => (int) $viewport['pageY']];
    }

    /**
     * Wait for the page's scroll position to settle after a wheel event.
     *
     * The browser applies wheel scrolling on the compositor thread and commits the new offset to
     * the main thread on a later frame, and the position can keep shifting afterwards (scroll
     * anchoring, overlays, lazy-loaded content), so waiting for an exact pre-computed target can
     * wait forever. Instead, return as soon as the expected target is reached, or once the
     * position stops changing across consecutive reads. A position that never left the start is
     * given a longer grace period, since the scroll may not have been committed yet or the wheel
     * event may have been consumed by the page.
     *
     * Yields the number of microseconds to wait between reads.
     *
     * @see Utils::tryWithTimeout
     *
     * @throws CommunicationException
     * @throws ResponseHasError
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
     */
    private function waitForScrollToSettle(int $startX, int $startY, int $targetX, int $targetY): Generator
    {
        $stableReads = 0;
        $lastX = $lastY = null;

        while (true) {
            $position = $this->getCurrentScrollPosition();

            if ($position['x'] === $targetX && $position['y'] === $targetY) {
                return;
            }

            if ($position['x'] === $lastX && $position['y'] === $lastY) {
                ++$stableReads;
            } else {
                $stableReads = 1;
                $lastX = $position['x'];
                $lastY = $position['y'];
            }

            $moved = $position['x'] !== $startX || $position['y'] !== $startY;

            if ($stableReads >= ($moved ? self::SCROLL_SETTLE_READS : self::SCROLL_UNMOVED_SETTLE_READS)) {
                return;
            }

            yield self::SCROLL_POLL_INTERVAL;
        }
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
