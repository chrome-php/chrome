<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\PageUtils;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Exception;
use HeadlessChromium\Exception\CommunicationException\ResponseHasError;
use HeadlessChromium\Exception\NavigationExpired;
use HeadlessChromium\Frame;
use HeadlessChromium\Page;
use HeadlessChromium\Utils;

/**
 * A class that is aimed to be used withing the method Page::navigate.
 *
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
     * @var ResponseReader
     */
    protected $navigateResponseReader;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var Page
     */
    protected $page;

    /**
     * @var bool
     */
    protected $strict;

    /**
     * PageNavigation constructor.
     *
     * @param Page   $page
     * @param string $url
     * @param bool   $strict by default this method will wait for the page to load even if a new navigation occurs
     *                       (ie: a new loader replaced the initial navigation). Passing $string to true will make the navigation to fail
     *                       if a new loader is generated
     *
     * @throws Exception\CommunicationException
     * @throws Exception\CommunicationException\CannotReadResponse
     * @throws Exception\CommunicationException\InvalidResponse
     */
    public function __construct(Page $page, string $url, bool $strict = false)
    {
        // make sure latest loaderId was pulled
        $page->getSession()->getConnection()->readData();

        // get previous loaderId for the navigation watcher
        $this->previousLoaderId = $page->getFrameManager()->getMainFrame()->getLatestLoaderId();

        // send navigation message
        $this->navigateResponseReader = $page->getSession()->sendMessage(
            new Message('Page.navigate', ['url' => $url])
        );

        $this->page = $page;
        $this->frame = $page->getFrameManager()->getMainFrame();
        $this->url = $url;
        $this->strict = $strict;
    }

    /**
     * Wait until the page loads.
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
     * @param string $eventName
     * @param int    $timeout   time in ms to wait for the navigation to complete. Default 30000 (30 seconds)
     *
     * @throws Exception\CommunicationException\CannotReadResponse
     * @throws Exception\CommunicationException\InvalidResponse
     * @throws Exception\NoResponseAvailable
     * @throws Exception\OperationTimedOut
     * @throws NavigationExpired
     * @throws ResponseHasError
     *
     * @return mixed
     */
    public function waitForNavigation($eventName = Page::LOAD, int $timeout = null)
    {
        if (null === $timeout) {
            $timeout = 30000;
        }

        return Utils::tryWithTimeout($timeout * 1000, $this->navigationComplete($eventName));
    }

    /**
     * To be used with @see Utils::tryWithTimeout.
     *
     * @param string $eventName
     *
     * @throws Exception\CommunicationException\CannotReadResponse
     * @throws Exception\CommunicationException\InvalidResponse
     * @throws Exception\NoResponseAvailable
     * @throws NavigationExpired
     * @throws ResponseHasError
     *
     * @return bool|\Generator
     */
    private function navigationComplete($eventName)
    {
        $delay = 500;

        while (true) {
            // read the response only if it was not read already
            if (!$this->navigateResponseReader->hasResponse()) {
                $this->navigateResponseReader->checkForResponse();
                if ($this->navigateResponseReader->hasResponse()) {
                    $response = $this->navigateResponseReader->getResponse();
                    if (!$response->isSuccessful()) {
                        throw new ResponseHasError(\sprintf('Cannot load page for url: "%s". Reason: %s', $this->url, $response->getErrorMessage()));
                    }

                    $this->currentLoaderId = $response->getResultData('loaderId');
                } else {
                    yield $delay;
                }
            }

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
                // if strict then throw or else replace the old navigation with the new one
                if ($this->strict) {
                    throw new NavigationExpired('The page has navigated to an other page and this navigation expired');
                } else {
                    $this->currentLoaderId = $this->frame->getLatestLoaderId();
                }
            }

            $this->page->getSession()->getConnection()->readData();
        }
    }
}
