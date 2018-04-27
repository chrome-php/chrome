<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Exception;
use HeadlessChromium\Exception\NavigationExpired;
use HeadlessChromium\Frame;
use HeadlessChromium\Page;
use HeadlessChromium\Utils;

/**
 * A class that is aimed to be used withing the method Page::navigate
 * @internal
 */
class PageNavigation
{
    /**
     * @var Frame
     */
    protected $frame;

    /**
     * @var string
     */
    protected $previousLoaderId;

    /**
     * @var string
     */
    protected $currentLoaderId;

    /**
     * PageNavigation constructor.
     * @param Page $page
     * @param string $previousLoaderId
     * @param string $currentLoaderId
     */
    public function __construct(Page $page, string $previousLoaderId, string $currentLoaderId)
    {
        $this->page = $page;
        $this->frame = $this->page->getFrameManager()->getMainFrame();
        $this->previousLoaderId = $previousLoaderId;
        $this->currentLoaderId = $currentLoaderId;
    }

    /**
     * Wait until the page loads
     *
     * Usage:
     *
     * ```php
     * $navigation = $page->navigate('http://example.com');
     * try {
     *      // wait max 30 seconds for dom content to load
     *      $navigation->waitForNavigation(Page::DOM_CONTENT_LOADED, 30000);
     * } catch (OperationTimedOut $e) {
     *      // too long to load
     * } catch (NavigationExpired $e) {
     *      // an other page loaded since this navigation was initiated
     * }
     * ```
     *
     * @param int $timeout
     * @param string $eventName
     * @return mixed|null
     * @throws Exception\CommunicationException\CannotReadResponse
     * @throws Exception\CommunicationException\InvalidResponse
     * @throws Exception\OperationTimedOut
     * @throws NavigationExpired
     */
    public function waitForNavigation($eventName = Page::LOAD, $timeout = 30000)
    {
        return Utils::tryWithTimeout($timeout * 1000, $this->navigationComplete($eventName));
    }

    /**
     * To be used with @see Utils::tryWithTimeout
     *
     * @param $eventName
     * @return bool|\Generator
     * @throws Exception\CommunicationException\CannotReadResponse
     * @throws Exception\CommunicationException\InvalidResponse
     * @throws NavigationExpired
     */
    private function navigationComplete($eventName)
    {
        $delay = 500;

        while (true) {
            // make sure that the current loader is the good one
            if ($this->frame->getLatestLoaderId() === $this->currentLoaderId) {
                // check that lifecycle event exists
                if ($this->page->hasLifecycleEvent($eventName)) {
                    return true;

                // or else just wait for the new event to trigger
                } else {
                    yield $delay;
                }

            // else if frame has still the previous loader, wait for the new one
            } elseif ($this->frame->getLatestLoaderId() == $this->previousLoaderId) {
                yield $delay;

            // else if a new loader is present that means that a new navigation started
            } else {
                throw new NavigationExpired(
                    'The page has navigated to an other page and this navigation expired'
                );
            }

            $this->page->getSession()->getConnection()->readData();
        }
    }
}
