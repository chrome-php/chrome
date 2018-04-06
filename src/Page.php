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

class Page
{

    const DOM_CONTENT_LOADED = 'DOMContentLoaded';
    const LOADED = 'loaded';

    /**
     * @var Target
     */
    protected $target;

    public function __construct(Target $target, array $frameTree)
    {
        $this->target = $target;
        $this->frameManager = new FrameManager($this, $frameTree);
    }

    /**
     * Get the session this page is attached to
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->target->getSession();
    }

    /**
     * @param $url
     * @return PageNavigation
     *
     * @throws NoResponseAvailable
     * @throws CommunicationException
     */
    public function navigate($url)
    {
        // make sure latest loaderId was pulled
        $this->getSession()->getConnection()->readData();

        // get previous loaderId for the navigation watcher
        $previousLoaderId = $this->frameManager->getMainFrame()->getLatestLoaderId();

        // set navigation message
        $response = $this->getSession()->sendMessageSync(new Message('Page.navigate', ['url' => $url]));

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
     * $response = $evaluation->waitForResponse();
     * ```
     *
     * @param string $expression
     * @return ResponseReader
     * @throws Exception\CommunicationException
     */
    public function evaluate(string $expression)
    {
        return $this->getSession()->sendMessage(
            new Message(
                'Runtime.evaluate',
                [
                    'awaitPromise' => true,
                    'returnByValue' => true,
                    'expression' => $expression
                ]
            )
        );
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
        $this->getSession()->getConnection()->readData();
        return $this->frameManager->getMainFrame()->getLifeCycle();
    }

    /**
     * Check if the lifecycle event was reached
     *
     * Example:
     *
     * ```php
     * $page->hasLifecycleEvent(Page::DOM_CONTENT_LOADED);
     * ```
     *
     * @param string $event
     * @return bool
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     */
    public function hasLifecycleEvent(string $event): bool
    {
        return array_key_exists($event, $this->getCurrentLifecycle());
    }
}
