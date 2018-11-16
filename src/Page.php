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
use HeadlessChromium\PageUtils\PageLayoutMetrics;
use HeadlessChromium\PageUtils\PageNavigation;
use HeadlessChromium\PageUtils\PageScreenshot;
use HeadlessChromium\PageUtils\PagePdf;
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
     * Page constructor.
     * @param Target $target
     * @param array $frameTree
     */
    public function __construct(Target $target, array $frameTree)
    {
        $this->target = $target;
        $this->frameManager = new FrameManager($this, $frameTree);
    }

    /**
     * Adds a script to be evaluated upon page navigation
     *
     * @param string $script
     * @param array $options
     *  - onLoad: defer script execution after page has loaded (useful for scripts that require the dom to be populated)
     * @throws CommunicationException
     * @throws NoResponseAvailable
     */
    public function addPreScript(string $script, array $options = [])
    {
        // defer script execution
        if (isset($options['onLoad']) && $options['onLoad']) {
            $script = 'window.onload = () => {' . $script . '}';
        }

        // add script
        $this->getSession()->sendMessageSync(
            new Message('Page.addScriptToEvaluateOnNewDocument', ['source' => $script])
        );
    }

    /**
     * Retrieves layout metrics of the page
     *
     * Example:
     *
     * ```php
     * $metrics = $page->getLayoutMetrics();
     * $contentSize = $metrics->getContentSize();
     * ```
     *
     * @return PageLayoutMetrics
     * @throws CommunicationException
     */
    public function getLayoutMetrics()
    {
        $this->assertNotClosed();

        $reader = $this->getSession()->sendMessage(
            new Message('Page.getLayoutMetrics')
        );

        return new PageLayoutMetrics($reader);
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
     * @param string $url
     * @param array $options
     *  - strict: make waitForNAvigation to fail if a new navigation is initiated. Default: false
     *
     * @return PageNavigation
     * @throws Exception\CommunicationException
     */
    public function navigate(string $url, array $options = [])
    {
        $this->assertNotClosed();

        return new PageNavigation($this, $url, $options['strict'] ?? false);
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
                    'expression' => $expression,
                    'userGesture' => true
                ]
            )
        );
        return new PageEvaluation($reader, $currentLoaderId, $this);
    }

    /**
     * Call a js function with the given argument in the page context
     *
     * Example:
     *
     * ```php
     * $evaluation = $page->callFunction('function(a, b) {return a + b}', [1, 2]);
     *
     * echo $evaluation->getReturnValue();
     * // 3
     * ```
     *
     * @param string $functionDeclaration
     * @param array $arguments
     * @return PageEvaluation
     * @throws CommunicationException
     */
    public function callFunction(string $functionDeclaration, array $arguments = []): PageEvaluation
    {
        $this->assertNotClosed();

        $currentLoaderId = $this->frameManager->getMainFrame()->getLatestLoaderId();
        $executionContextId = $this->frameManager->getMainFrame()->getExecutionContextId();
        $reader = $this->getSession()->sendMessage(
            new Message(
                'Runtime.callFunctionOn',
                [
                    'functionDeclaration' => $functionDeclaration,
                    'arguments' => array_map(function ($arg) {
                        return [
                            'value' => $arg
                        ];
                    }, $arguments),
                    'executionContextId' => $executionContextId,
                    'awaitPromise' => true,
                    'returnByValue' => true,
                    'userGesture' => true
                ]
            )
        );

        return new PageEvaluation($reader, $currentLoaderId, $this);
    }

    /**
     * Add a script tag to the page (ie. <script>)
     *
     * Example:
     *
     * ```php
     * $evaluation = $page->addScriptTag(['content' => file_get_content('jquery.js')]);
     * $evaluation->waitForResponse();
     * ```
     *
     * @param array $options
     * @return PageEvaluation
     * @throws CommunicationException
     */
    public function addScriptTag(array $options): PageEvaluation
    {
        if (isset($options['url']) && isset($options['content'])) {
            throw new \InvalidArgumentException('addScript accepts "url" or "content" option, not both');
        } elseif (isset($options['url'])) {
            $scriptFunction = 'async function(src) {
                const script = document.createElement("script");
                script.type = "text/javascript";
                script.src = src;
                
                const promise = new Promise((res, rej) => {
                    script.onload = res;
                    script.onerror = rej;
                });
                
                document.head.appendChild(script);
                await promise;
            }';
            $arguments = [$options['url']];
        } elseif (isset($options['content'])) {
            $scriptFunction = 'async function(scriptContent) {
                var script = document.createElement("script");
                script.type = "text/javascript";
                script.text = scriptContent;
                
                let error = null;
                script.onerror = e => {error = e};
                
                document.head.appendChild(script);
                
                if (error) {
                    throw error;
                }
            }';
            $arguments = [$options['content']];
        } else {
            throw new \InvalidArgumentException('addScript requires one of "url" or "content" option');
        }

        return $this->callFunction($scriptFunction, $arguments);
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
     * Get a clip that uses the full layout page, not only the viewport
     *
     * This method is synchronous
     *
     * Fullpage screenshot exemple:
     *
     * ```php
     *     $page
     *      ->screenshot([
     *          'clip' => $page->getFullPageClip()
     *      ])
     *      ->saveToFile('/tmp/image.jpg');
     * ```
     *
     * @return Clip
     */
    public function getFullPageClip(): Clip
    {
        $contentSize = $this->getLayoutMetrics()->await()->getContentSize();
        return new Clip(0, 0, $contentSize['width'], $contentSize['height']);
    }

    /**
     * Take a screenshot
     *
     * Usage:
     *
     * ```php
     * $page->screenshot()->saveToFile('/tmp/image.jpg');
     * ```
     *
     * @param array $options
     *  - format: "png"|"jpg" default "png"
     *  - quality: number from 0 to 100. Only for jpegs
     *  - clip: instance of a Clip to choose an area for the screenshot
     *
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
     * Generate a PDF
     * Usage:
     *
     * ```php
     * $page->pdf()->saveToFile('/tmp/file.pdf');
     * ```
     *
     * @param array $options
     *  - landscape: default false
     *  - printBackground: default false
     * @return PagePdf
     * @throws CommunicationException
     */
    public function pdf(array $options = []): PagePdf
    {
        $this->assertNotClosed();

        $pdfOptions = [];

        // is landscape?
        if (array_key_exists('landscape', $options)) {
            // landscape requires type to be boolean
            if (!is_bool($options['landscape'])) {
                throw new \InvalidArgumentException(
                    'Invalid options "landscape" for print to pdf. Must be true or false'
                );
            }
            $pdfOptions['landscape'] = $options['landscape'];
        }

        // should print background?
        if (array_key_exists('printBackground', $options)) {
            // printBackground requires type to be boolean
            if (!is_bool($options['printBackground'])) {
                throw new \InvalidArgumentException(
                    'Invalid options "printBackground" for print to pdf. Must be true or false'
                );
            }
            $pdfOptions['printBackground'] = $options['printBackground'];
        }
    
        // option displayHeaderFooter
        if (array_key_exists('displayHeaderFooter', $options)) {
            // printBackground requires type to be boolean
            if (!is_bool($options['displayHeaderFooter'])) {
                throw new \InvalidArgumentException(
                    'Invalid options "displayHeaderFooter" for print to pdf. Must be true or false'
                );
            }
            $pdfOptions['displayHeaderFooter'] = $options['displayHeaderFooter'];
        }
    
        // option marginTop
        if (array_key_exists('marginTop', $options)) {
            // marginTop requires type to be float
            if (gettype($options['marginTop']) !== 'double') {
                throw new \InvalidArgumentException(
                    'Invalid options "marginTop" for print to pdf. Must be float like 1.0 or 5.4'
                );
            }
            $pdfOptions['marginTop'] = $options['marginTop'];
        }
    
        // option marginBottom
        if (array_key_exists('marginBottom', $options)) {
            // marginBottom requires type to be float
            if (gettype($options['marginBottom']) !== 'double') {
                throw new \InvalidArgumentException(
                    'Invalid options "marginBottom" for print to pdf. Must be float like 1.0 or 5.4'
                );
            }
            $pdfOptions['marginBottom'] = $options['marginBottom'];
        }
    
        // option marginLeft
        if (array_key_exists('marginLeft', $options)) {
            // marginBottom requires type to be float
            if (gettype($options['marginLeft']) !== 'double') {
                throw new \InvalidArgumentException(
                    'Invalid options "marginLeft" for print to pdf. Must be float like 1.0 or 5.4'
                );
            }
            $pdfOptions['marginLeft'] = $options['marginLeft'];
        }
    
        // option marginLeft
        if (array_key_exists('marginRight', $options)) {
            // marginBottom requires type to be float
            if (gettype($options['marginRight']) !== 'double') {
                throw new \InvalidArgumentException(
                    'Invalid options "marginRight" for print to pdf. Must be float like 1.0 or 5.4'
                );
            }
            $pdfOptions['marginRight'] = $options['marginRight'];
        }
    
        // option preferCSSPageSize
        if (array_key_exists('preferCSSPageSize', $options)) {
            // printBackground requires type to be boolean
            if (!is_bool($options['preferCSSPageSize'])) {
                throw new \InvalidArgumentException(
                    'Invalid options "preferCSSPageSize" for print to pdf. Must be true or false'
                );
            }
            $pdfOptions['preferCSSPageSize'] = $options['preferCSSPageSize'];
        }
        
        // request pdf
        $responseReader = $this->getSession()
            ->sendMessage(new Message('Page.printToPDF', $pdfOptions));

        return new PagePdf($responseReader);
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
