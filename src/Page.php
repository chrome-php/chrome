<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\Session;
use HeadlessChromium\Communication\Target;
use HeadlessChromium\Cookies\Cookie;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\TargetDestroyed;
use HeadlessChromium\Input\Mouse;
use HeadlessChromium\PageUtils\CookiesGetter;
use HeadlessChromium\PageUtils\PageEvaluation;
use HeadlessChromium\PageUtils\PageNavigation;
use HeadlessChromium\PageUtils\PageScreenshot;
use HeadlessChromium\PageUtils\ResponseWaiter;

class Page
{

    const DOM_CONTENT_LOADED = 'DOMContentLoaded';
    const LOAD = 'load';
    const NETWORK_IDLE = 'networkIdle';

    /**
     * @var Target
     */
    protected $target;

    /**
     * @var FrameManager
     */
    protected $frameManager;

    /**
     * @var Mouse|Null
     */
    protected $mouse;

    /**
     * @var array
     */
    protected $options = [];

    public function __construct(Target $target, array $frameTree, array $options = [])
    {
        $this->target = $target;
        $this->frameManager = new FrameManager($this, $frameTree);
        $this->options = $options;
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
     * @return PageNavigation
     */
    public function navigate($url, ?string $preScript = NULL)
    {
        $this->assertNotClosed();

        if (isset($this->options['preScript'])){
            $preScript = $this->options['preScript'].$preScript;
        }

        return new PageNavigation($this, $url, $preScript);
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
     *
     * @param string $eventName
     * @param int $timeout
     * @param null $loaderId
     * @return $this
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     * @throws Exception\OperationTimedOut
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
     * Allows to change viewport size, enabling mobile mode, or changing the scale factor
     *
     * usage:
     *
     * ```
     * $page->setDeviceMetricsOverride
     * ```
     * @param $overrides
     * @throws CommunicationException
     * @throws NoResponseAvailable
     *
     * @return ResponseWaiter
     *
     */
    public function setDeviceMetricsOverride(array $overrides)
    {
        if (!array_key_exists('width', $overrides)) {
            $overrides['width'] = 0;
        }
        if (!array_key_exists('height', $overrides)) {
            $overrides['height'] = 0;
        }
        if (!array_key_exists('deviceScaleFactor', $overrides)) {
            $overrides['deviceScaleFactor'] = 0;
        }
        if (!array_key_exists('mobile', $overrides)) {
            $overrides['mobile'] = false;
        }

        $this->assertNotClosed();
        return new ResponseWaiter($this->getSession()->sendMessage(
            new Message('Emulation.setDeviceMetricsOverride', $overrides)
        ));
    }

    /**
     * Set viewport size
     *
     * @param int $width
     * @param int $height
     * @throws CommunicationException
     * @throws NoResponseAvailable
     *
     * @return ResponseWaiter
     */
    public function setViewport(int $width, int $height)
    {
        return $this->setDeviceMetricsOverride([
            'width' => $width,
            'height' => $height
        ]);
    }

    /**
     * Get mouse object to play with
     * @return Mouse
     */
    public function mouse()
    {
        if (!$this->mouse) {
            $this->mouse = new Mouse($this);
        }

        return $this->mouse;
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
    public function assertNotClosed()
    {
        if ($this->target->isDestroyed()) {
            throw new TargetDestroyed('The page was closed and is not available anymore.');
        }
    }

    /**
     * Gets the current url of the page, always in sync with the browser.
     *
     * @return mixed|null
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     */
    public function getCurrentUrl()
    {
        // ensure target is not closed
        $this->assertNotClosed();

        // ensure target info are updated
        $this->getSession()->getConnection()->readData();

        // get url from target info
        return $this->target->getTargetInfo('url');
    }

    /**
     * Read cookies for the current page
     *
     * usage:
     *
     * ```
     *   $page->readCookies()->await()->getCookies();
     * ```
     *
     * @see getCookies
     * @see readAllCookies
     * @see getAllCookies
     *
     * @return CookiesGetter
     * @throws CommunicationException
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     */
    public function readCookies()
    {
        // ensure target is not closed
        $this->assertNotClosed();

        // read cookies async
        $response = $this->getSession()->sendMessage(
            new Message(
                'Network.getCookies',
                [
                    'urls' => [$this->getCurrentUrl()]
                ]
            )
        );

        // return async helper
        return new CookiesGetter($response);
    }

    /**
     * Read all cookies in the browser
     *
     * @see getCookies
     * @see readCookies
     * @see getAllCookies
     *
     * ```
     *   $page->readCookies()->await()->getCookies();
     * ```
     * @return CookiesGetter
     * @throws CommunicationException
     */
    public function readAllCookies()
    {
        // ensure target is not closed
        $this->assertNotClosed();

        // read cookies async
        $response = $this->getSession()->sendMessage(new Message('Network.getAllCookies'));

        // return async helper
        return new CookiesGetter($response);
    }

    /**
     * Get cookies for the current page synchronously
     *
     * @see readCookies
     * @see readAllCookies
     * @see getAllCookies
     *
     * @param int|null $timeout
     * @return CookiesCollection
     * @throws CommunicationException
     * @throws Exception\OperationTimedOut
     * @throws NoResponseAvailable
     */
    public function getCookies(int $timeout = null)
    {
        return $this->readCookies()->await($timeout)->getCookies();
    }

    /**
     * Get all browser cookies synchronously
     *
     * @see getCookies
     * @see readAllCookies
     * @see readCookies
     *
     * @param int|null $timeout
     * @return CookiesCollection
     * @throws CommunicationException
     * @throws Exception\OperationTimedOut
     * @throws NoResponseAvailable
     */
    public function getAllCookies(int $timeout = null)
    {
        return $this->readAllCookies()->await($timeout)->getCookies();
    }

    /**
     * @param Cookie[]|CookiesCollection $cookies
     */
    public function setCookies($cookies)
    {
        // define params to send in cookie message
        $allowedParams = ['url', 'domain', 'path', 'secure', 'httpOnly', 'sameSite', 'expires'];

        // init list of cookies to send
        $browserCookies = [];

        // feed list of cookies to send
        foreach ($cookies as $cookie) {
            $browserCookie = [
                'name' => $cookie->getName(),
                'value' => $cookie->getValue(),
            ];

            foreach ($allowedParams as $param) {
                if ($cookie->offsetExists($param)) {
                    $browserCookie[$param] = $cookie->offsetGet($param);
                }
            }

            // set domain from current page
            if (!isset($browserCookie['domain'])) {
                $browserCookie['domain'] = parse_url($this->getCurrentUrl(), PHP_URL_HOST);
            }

            $browserCookies[] = $browserCookie;
        }

        // send cookies
        $response = $this->getSession()
            ->sendMessage(
                new Message('Network.setCookies', ['cookies' => $browserCookies])
            );

        // return async helper
        return new ResponseWaiter($response);
    }

    /**
     * Set user agent for the current page
     * @param string $userAgent
     * @return ResponseWaiter
     * @throws CommunicationException
     */
    public function setUserAgent(string $userAgent)
    {
        $response = $this->getSession()
            ->sendMessage(
                new Message('Network.setUserAgentOverride', ['userAgent' => $userAgent])
            );

        return new ResponseWaiter($response);
    }
}
