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

use Generator;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Exception;
use HeadlessChromium\Exception\CommunicationException\ResponseHasError;
use HeadlessChromium\Exception\NavigationExpired;
use HeadlessChromium\Frame;
use HeadlessChromium\Page;
use HeadlessChromium\Utils;
use InvalidArgumentException;

/**
 * A class that is aimed to be used withing the method Page::navigate.
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
     * @param array  $options
     *                        - strict: make waitForNavigation to fail if a new navigation is initiated. Default: false
     *                        - referrer: the referrer URL to use for the navigation
     *                        - referrerPolicy: the referrer policy to use for the navigation
     *                        - transitionType: the transition type to use for the navigation
     *
     * @throws Exception\CommunicationException
     * @throws Exception\CommunicationException\CannotReadResponse
     * @throws Exception\CommunicationException\InvalidResponse
     *
     * @internal
     */
    public function __construct(Page $page, string $url, array $options = [])
    {
        $params = ['url' => $url];

        foreach ($options as $key => $value) {
            if (\in_array($key, ['referrer', 'referrerPolicy', 'transitionType'], true)) {
                $params[$key] = $value;
            } elseif ('strict' !== $key) {
                throw new InvalidArgumentException('Invalid option "'.$key.'" for page navigation. Supported options are "strict", "referrer", "referrerPolicy" and "transitionType".');
            }
        }

        // make sure latest loaderId was pulled
        $page->getSession()->getConnection()->readData();

        // get previous loaderId for the navigation watcher
        $this->previousLoaderId = $page->getFrameManager()->getMainFrame()->getLatestLoaderId();

        // send navigation message
        $this->navigateResponseReader = $page->getSession()->sendMessage(
            new Message('Page.navigate', $params)
        );

        $this->page = $page;
        $this->frame = $page->getFrameManager()->getMainFrame();
        $this->url = $url;
        $this->strict = $options['strict'] ?? false;
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
    public function waitForNavigation($eventName = Page::LOAD, ?int $timeout = null)
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
     * @return bool|Generator
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
                }
                yield $delay;

            // else if frame has still the previous loader, wait for the new one
            } elseif ($this->frame->getLatestLoaderId() == $this->previousLoaderId) {
                yield $delay;

            // else if a new loader is present that means that a new navigation started
            } else {
                // if strict then throw or else replace the old navigation with the new one
                if ($this->strict) {
                    throw new NavigationExpired('The page has navigated to an other page and this navigation expired');
                }
                $this->currentLoaderId = $this->frame->getLatestLoaderId();
            }

            $this->page->getSession()->getConnection()->readData();
        }
    }
}
