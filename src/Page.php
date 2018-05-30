<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Communication\Session;
use HeadlessChromium\Communication\Target;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\CommunicationException\ResponseHasError;
use HeadlessChromium\Exception\TargetDestroyed;
use HeadlessChromium\PageUtils\PageEvaluation;
use HeadlessChromium\PageUtils\PageNavigation;
use HeadlessChromium\PageUtils\PageScreenshot;

class Page
{

    const DOM_CONTENT_LOADED = 'DOMContentLoaded';
    const LOAD = 'load';

    /**
     * @var Target
     */
    protected $target;

    /**
     * @var FrameManager
     */
    protected $frameManager;

    public function __construct(Target $target, array $frameTree)
    {
        $this->target = $target;
        $this->frameManager = new FrameManager($this, $frameTree);
    }

    /**
     * @return FrameManager
     */
    public function getFrameManager(): FrameManager
    {
        $this->assertNotClosed();

        return $this->frameManager;
    }

    /**
     * Get the session this page is attached to
     * @return Session
     */
    public function getSession(): Session
    {
        $this->assertNotClosed();

        return $this->target->getSession();
    }

    /**
     * @param $url
     * @param int $timeout
     * @return PageNavigation
     *
     * @throws NoResponseAvailable
     * @throws CommunicationException
     */
    public function navigate($url, $timeout = null)
    {
        $this->assertNotClosed();

        // make sure latest loaderId was pulled
        $this->getSession()->getConnection()->readData();

        // get previous loaderId for the navigation watcher
        $previousLoaderId = $this->frameManager->getMainFrame()->getLatestLoaderId();

        // set navigation message
        $response = $this->getSession()->sendMessageSync(new Message('Page.navigate', ['url' => $url]), $timeout);

        // make sure navigation has no error
        if (!$response->isSuccessful()) {
            throw new ResponseHasError(
                sprintf('Cannot load page for url: "%s". Reason: %s', $url, $response->getErrorMessage())
            );
        }

        // create PageNavigation instance
        $loaderId = $response->getResultData('loaderId');
        return new PageNavigation($this, $previousLoaderId, $loaderId);
    }

    /**
     * Evaluates the given string in the page context
     *
     * Example:
     *
     * ```php
     * $evaluation = $page->evaluate('document.querySelector("title").innerHTML');
     * $response = $evaluation->getReturnValue();
     * ```
     *
     * @param string $expression
     * @return PageEvaluation
     * @throws Exception\CommunicationException
     */
    public function evaluate(string $expression)
    {
        $this->assertNotClosed();

        $currentLoaderId = $this->frameManager->getMainFrame()->getLatestLoaderId();
        $reader = $this->getSession()->sendMessage(
            new Message(
                'Runtime.evaluate',
                [
                    'awaitPromise' => true,
                    'returnByValue' => true,
                    'expression' => $expression
                ]
            )
        );
        return new PageEvaluation($reader, $currentLoaderId, $this);
    }

    /**
     * Gets the lifecycle of the main frame of the page.
     *
     * Events come as an associative array with event name as keys and time they occurred at in values.
     *
     * @return array
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     */
    public function getCurrentLifecycle()
    {
        $this->assertNotClosed();

        $this->getSession()->getConnection()->readData();
        return $this->frameManager->getMainFrame()->getLifeCycle();
    }

    /**
     * Check if the lifecycle event was reached
     *
     * Example:
     *
     * ```php
     * $page->hasLifecycleEvent(Page::DOM_CONTENT_LOAD);
     * ```
     *
     * @param string $event
     * @return bool
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     */
    public function hasLifecycleEvent(string $event): bool
    {
        $this->assertNotClosed();

        return array_key_exists($event, $this->getCurrentLifecycle());
    }

    /**
     * Wait for the page to unload
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     * @throws Exception\OperationTimedOut
     *
     * @return $this
     */
    public function waitForReload($eventName = Page::LOAD, $timeout = 30000, $loaderId = null)
    {
        $this->assertNotClosed();

        if (!$loaderId) {
            $loaderId = $loader = $this->frameManager->getMainFrame()->getLatestLoaderId();
        }

        Utils::tryWithTimeout($timeout * 1000, $this->waitForReloadGenerator($eventName, $loaderId));
        return $this;
    }

    /**
     * @param $loaderId
     * @return bool|\Generator
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     * @internal
     */
    private function waitForReloadGenerator($eventName, $loaderId)
    {
        $delay = 500;

        while (true) {
            // make sure that the current loader is the good one
            if ($this->frameManager->getMainFrame()->getLatestLoaderId() !== $loaderId) {
                if ($this->hasLifecycleEvent($eventName)) {
                    return true;
                }

                yield $delay;

                // else if frame has still the previous loader, wait for the new one
            } else {
                yield $delay;
            }

            $this->getSession()->getConnection()->readData();
        }
    }

    /**
     *
     * Example:
     *
     * ```php
     * $page->screenshot()->saveToFile();
     * ```
     *
     * @param array $options
     * @return PageScreenshot
     * @throws CommunicationException
     */
    public function screenshot(array $options = []): PageScreenshot
    {
        $this->assertNotClosed();

        $screenshotOptions = [];

        // get format
        if (array_key_exists('format', $options)) {
            $screenshotOptions['format'] = $options['format'];
        } else {
            $screenshotOptions['format'] = 'png';
        }

        // make sure format is valid
        if (!in_array($screenshotOptions['format'], ['png', 'jpeg'])) {
            throw new \InvalidArgumentException(
                'Invalid options "format" for page screenshot. Format must be "png" or "jpeg".'
            );
        }

        // get quality
        if (array_key_exists('quality', $options)) {
            // quality requires type to be jpeg
            if ($screenshotOptions['format'] !== 'jpeg') {
                throw new \InvalidArgumentException(
                    'Invalid options "quality" for page screenshot. Quality requires the image format to be "jpeg".'
                );
            }

            // quality must be an integer
            if (!is_int($options['quality'])) {
                throw new \InvalidArgumentException(
                    'Invalid options "quality" for page screenshot. Quality must be an integer value.'
                );
            }

            // quality must be between 0 and 100
            if ($options['quality'] < 0 || $options['quality'] > 100) {
                throw new \InvalidArgumentException(
                    'Invalid options "quality" for page screenshot. Quality must be comprised between 0 and 100.'
                );
            }

            // set quality
            $screenshotOptions['quality'] = $options['quality'];
        }

        // clip
        if (array_key_exists('clip', $options)) {
            // make sure it's a Clip instance
            if (!($options['clip'] instanceof Clip)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid options "clip" for page screenshot, it must be a %s instance.', Clip::class)
                );
            }

            // add to params
            $screenshotOptions['clip'] = [
                'x' => $options['clip']->getX(),
                'y' => $options['clip']->getY(),
                'width' => $options['clip']->getWidth(),
                'height' => $options['clip']->getHeight(),
                'scale' => $options['clip']->getScale()
            ];
        }

        // request screenshot
        $responseReader = $this->getSession()
            ->sendMessage(new Message('Page.captureScreenshot', $screenshotOptions));

        return new PageScreenshot($responseReader);
    }

    /**
     * Request to close the page
     * @throws CommunicationException
     */
    public function close()
    {

        $this->assertNotClosed();

        $this->getSession()
            ->getConnection()
            ->sendMessage(
                new Message(
                    'Target.closeTarget',
                    ['targetId' => $this->getSession()->getTargetId()]
                )
            );

        // TODO return close waiter
    }

    /**
     * Throws if the page was closed
     */
    private function assertNotClosed()
    {
        if ($this->target->isDestroyed()) {
            throw new TargetDestroyed('The page was closed and is not available anymore.');
        }
    }
}
