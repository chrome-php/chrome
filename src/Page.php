<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\ResponseReader;
use HeadlessChromium\Communication\Session;
use HeadlessChromium\Communication\Target;

class Page
{

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
     * @return ResponseReader
     * @throws Exception\NoResponseAvailable
     */
    public function navigate($url)
    {
        return $this->getSession()->sendMessage(new Message('Page.navigate', ['url' => $url]));
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
     * @throws Exception\NoResponseAvailable
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
     */
    public function getCurrentLifecycle()
    {
        return $this->frameManager->getMainFrame()->getLifeCycle();
    }

    public function waitForLifecycleEvent($event = null)
    {
        // TODO
    }
}
