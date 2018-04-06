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
    const LOAD = 'load';

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
     * $response = $evaluation->getReturnValue();
     * ```
     *
     * @param string $expression
     * @return PageEvaluation
     * @throws Exception\CommunicationException
     */
    public function evaluate(string $expression)
    {
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

        return new PageEvaluation($reader);
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
        return array_key_exists($event, $this->getCurrentLifecycle());
    }


    /**
     *
     */
    public function screenshot(array $options = []): PageScreenshot
    {
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

        // TODO document clip
        // TODO validate clip/use viewport object
        if (array_key_exists('clip', $options)) {
            $screenshotOptions['clip'] = $options['clip'];
        }

        // request screen shot
        $responseReader = $this->getSession()
            ->sendMessage(new Message('Page.captureScreenshot', $screenshotOptions));

        return new PageScreenshot($responseReader);
    }
}
