<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\Session;
use HeadlessChromium\Communication\Target;
use HeadlessChromium\Cookies\Cookie;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Dom\Dom;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\InvalidTimezoneId;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\TargetDestroyed;
use HeadlessChromium\Input\Keyboard;
use HeadlessChromium\Input\Mouse;
use HeadlessChromium\PageUtils\CookiesGetter;
use HeadlessChromium\PageUtils\PageEvaluation;
use HeadlessChromium\PageUtils\PageLayoutMetrics;
use HeadlessChromium\PageUtils\PageNavigation;
use HeadlessChromium\PageUtils\PagePdf;
use HeadlessChromium\PageUtils\PageScreenshot;
use HeadlessChromium\PageUtils\ResponseWaiter;

class Page
{
    public const DOM_CONTENT_LOADED = 'DOMContentLoaded';
    public const LOAD = 'load';
    public const NETWORK_IDLE = 'networkIdle';

    /**
     * @var Target
     */
    protected $target;

    /**
     * @var FrameManager
     */
    protected $frameManager;

    /**
     * @var Mouse|null
     */
    protected $mouse;

    /**
     * @var Keyboard|null
     */
    protected $keyboard;

    /**
     * Page constructor.
     *
     * @param Target $target
     * @param array  $frameTree
     */
    public function __construct(Target $target, array $frameTree)
    {
        $this->target = $target;
        $this->frameManager = new FrameManager($this, $frameTree);
    }

    /**
     * Adds a script to be evaluated upon page navigation.
     *
     * @param string $script
     * @param array  $options
     *                        - onLoad: defer script execution after page has loaded (useful for scripts that require the dom to be populated)
     *
     * @throws CommunicationException
     * @throws NoResponseAvailable
     */
    public function addPreScript(string $script, array $options = []): void
    {
        // defer script execution
        if (isset($options['onLoad']) && $options['onLoad']) {
            $script = 'window.onload = () => {'.$script.'}';
        }

        // add script
        $this->getSession()->sendMessageSync(
            new Message('Page.addScriptToEvaluateOnNewDocument', ['source' => $script])
        );
    }

    public function authenticate(string $username, string $password): void
    {
        $this->getSession()->sendMessageSync(new Message(
            'Fetch.enable',
            [
                'patterns' => [
                    [
                        'urlPattern' => '*',
                    ],
                ],
                'handleAuthRequests' => true,
            ]
        ));

        $this->getSession()->on('method:Fetch.requestPaused', function (array $params): void {
            $this->getSession()->sendMessageSync(new Message(
                'Fetch.continueRequest',
                [
                    'requestId' => $params['requestId'],
                ]
            ));
        });

        $this->getSession()->on('method:Fetch.authRequired', function (array $params) use ($username, $password): void {
            $this->getSession()->sendMessageSync(new Message(
                'Fetch.continueWithAuth',
                [
                    'requestId' => $params['requestId'],
                    'authChallengeResponse' => [
                        'response' => 'ProvideCredentials',
                        'username' => $username,
                        'password' => $password,
                    ],
                ],
            ));

            $this->getSession()->sendMessageSync(new Message('Fetch.disable'));
        });
    }

    /**
     * Retrieves layout metrics of the page.
     *
     * Example:
     *
     * ```php
     * $metrics = $page->getLayoutMetrics();
     * $contentSize = $metrics->getContentSize();
     * ```
     *
     * @throws CommunicationException
     *
     * @return PageLayoutMetrics
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
     * Get the session this page is attached to.
     *
     * @return Session
     */
    public function getSession(): Session
    {
        $this->assertNotClosed();

        return $this->target->getSession();
    }

    /**
     * Sets the HTTP header necessary for basic authentication.
     *
     * @param string $username
     * @param string $password
     */
    public function setBasicAuthHeader(string $username, string $password): void
    {
        $header = \base64_encode($username.':'.$password);
        $this->getSession()->sendMessage(new Message(
            'Network.setExtraHTTPHeaders',
            ['headers' => ['Authorization' => 'Basic '.$header]]
        ));
    }

    /**
     * Sets the path to save downloaded files.
     *
     * @param string $path
     */
    public function setDownloadPath(string $path): void
    {
        $this->getSession()->sendMessage(new Message(
            'Page.setDownloadBehavior',
            ['behavior' => 'allow', 'downloadPath' => $path]
        ));
    }

    /**
     * @param string $url
     * @param array  $options
     *                        - strict: make waitForNAvigation to fail if a new navigation is initiated. Default: false
     *
     * @throws Exception\CommunicationException
     *
     * @return PageNavigation
     */
    public function navigate(string $url, array $options = [])
    {
        $this->assertNotClosed();

        return new PageNavigation($this, $url, $options['strict'] ?? false);
    }

    /**
     * Evaluates the given string in the page context.
     *
     * Example:
     *
     * ```php
     * $evaluation = $page->evaluate('document.querySelector("title").innerHTML');
     * $response = $evaluation->getReturnValue();
     * ```
     *
     * @param string $expression
     *
     * @throws Exception\CommunicationException
     *
     * @return PageEvaluation
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
                    'userGesture' => true,
                ]
            )
        );

        return new PageEvaluation($reader, $currentLoaderId, $this);
    }

    /**
     * Call a js function with the given argument in the page context.
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
     * @param array  $arguments
     *
     * @throws CommunicationException
     *
     * @return PageEvaluation
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
                    'arguments' => \array_map(function ($arg) {
                        return [
                            'value' => $arg,
                        ];
                    }, $arguments),
                    'executionContextId' => $executionContextId,
                    'awaitPromise' => true,
                    'returnByValue' => true,
                    'userGesture' => true,
                ]
            )
        );

        return new PageEvaluation($reader, $currentLoaderId, $this);
    }

    /**
     * Add a script tag to the page (ie. <script>).
     *
     * Example:
     *
     * ```php
     * $evaluation = $page->addScriptTag(['content' => file_get_content('jquery.js')]);
     * $evaluation->waitForResponse();
     * ```
     *
     * @param array $options
     *
     * @throws CommunicationException
     *
     * @return PageEvaluation
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
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     *
     * @return array
     */
    public function getCurrentLifecycle()
    {
        $this->assertNotClosed();

        $this->getSession()->getConnection()->readData();

        return $this->frameManager->getMainFrame()->getLifeCycle();
    }

    /**
     * Check if the lifecycle event was reached.
     *
     * Example:
     *
     * ```php
     * $page->hasLifecycleEvent(Page::DOM_CONTENT_LOAD);
     * ```
     *
     * @param string $event
     *
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     *
     * @return bool
     */
    public function hasLifecycleEvent(string $event): bool
    {
        $this->assertNotClosed();

        return \array_key_exists($event, $this->getCurrentLifecycle());
    }

    /**
     * Wait for the page to unload.
     *
     * @param string $eventName
     * @param int    $timeout
     * @param null   $loaderId
     *
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     * @throws Exception\OperationTimedOut
     *
     * @return $this
     */
    public function waitForReload($eventName = self::LOAD, $timeout = 30000, $loaderId = null)
    {
        $this->assertNotClosed();

        if (!$loaderId) {
            $loaderId = $loader = $this->frameManager->getMainFrame()->getLatestLoaderId();
        }

        Utils::tryWithTimeout($timeout * 1000, $this->waitForReloadGenerator($eventName, $loaderId));

        return $this;
    }

    /**
     * @param string $eventName
     * @param string $loaderId
     *
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     *
     * @return bool|\Generator
     *
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
     * Get a clip that uses the full layout page, not only the viewport.
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
     * @param int|null $timeout
     *
     * @return Clip
     */
    public function getFullPageClip(int $timeout = null): Clip
    {
        $contentSize = $this->getLayoutMetrics()->await($timeout)->getContentSize();

        return new Clip(0, 0, $contentSize['width'], $contentSize['height']);
    }

    /**
     * Take a screenshot.
     *
     * Usage:
     *
     * ```php
     * $page->screenshot()->saveToFile('/tmp/image.jpg');
     * ```
     *
     * @param array $options
     *                       - format: "png"|"jpg" default "png"
     *                       - quality: number from 0 to 100. Only for jpegs
     *                       - clip: instance of a Clip to choose an area for the screenshot
     *                       - captureBeyondViewport: whether to capture the screenshot beyond the viewport. Defaults to false
     *
     * @throws CommunicationException
     *
     * @return PageScreenshot
     */
    public function screenshot(array $options = []): PageScreenshot
    {
        $this->assertNotClosed();

        $screenshotOptions = [];

        if (\array_key_exists('captureBeyondViewport', $options)) {
            $screenshotOptions['captureBeyondViewport'] = $options['captureBeyondViewport'];
        }

        // get format
        if (\array_key_exists('format', $options)) {
            $screenshotOptions['format'] = $options['format'];
        } else {
            $screenshotOptions['format'] = 'png';
        }

        // make sure format is valid
        if (!\in_array($screenshotOptions['format'], ['png', 'jpeg'])) {
            throw new \InvalidArgumentException('Invalid options "format" for page screenshot. Format must be "png" or "jpeg".');
        }

        // get quality
        if (\array_key_exists('quality', $options)) {
            // quality requires type to be jpeg
            if ('jpeg' !== $screenshotOptions['format']) {
                throw new \InvalidArgumentException('Invalid options "quality" for page screenshot. Quality requires the image format to be "jpeg".');
            }

            // quality must be an integer
            if (!\is_int($options['quality'])) {
                throw new \InvalidArgumentException('Invalid options "quality" for page screenshot. Quality must be an integer value.');
            }

            // quality must be between 0 and 100
            if ($options['quality'] < 0 || $options['quality'] > 100) {
                throw new \InvalidArgumentException('Invalid options "quality" for page screenshot. Quality must be comprised between 0 and 100.');
            }

            // set quality
            $screenshotOptions['quality'] = $options['quality'];
        }

        // clip
        if (\array_key_exists('clip', $options)) {
            // make sure it's a Clip instance
            if (!($options['clip'] instanceof Clip)) {
                throw new \InvalidArgumentException(\sprintf('Invalid options "clip" for page screenshot, it must be a %s instance.', Clip::class));
            }

            // add to params
            $screenshotOptions['clip'] = [
                'x' => $options['clip']->getX(),
                'y' => $options['clip']->getY(),
                'width' => $options['clip']->getWidth(),
                'height' => $options['clip']->getHeight(),
                'scale' => $options['clip']->getScale(),
            ];
        }

        // request screenshot
        $responseReader = $this->getSession()
            ->sendMessage(new Message('Page.captureScreenshot', $screenshotOptions));

        return new PageScreenshot($responseReader);
    }

    /**
     * Generate a PDF
     * Usage:.
     *
     * ```php
     * $page->pdf()->saveToFile('/tmp/file.pdf');
     * ```
     *
     * @param array $options
     *                       - landscape: default false
     *                       - printBackground: default false
     *                       - displayHeaderFooter: default false
     *                       - headerTemplate: HTML template for the print header (see docs for details)
     *                       - footerTemplate: HTML template for the print footer (see docs for details)
     *                       - paperWidth: default 8.5 inches
     *                       - paperHeight: default 11 inches
     *                       - marginTop: default 1 cm
     *                       - marginBottom: default 1 cm
     *                       - marginLeft: default 1 cm
     *                       - marginRight: default 1 cm
     *                       - preferCSSPageSize: default false
     *                       - scale: default 1
     *
     * @throws CommunicationException
     *
     * @return PagePdf
     */
    public function pdf(array $options = []): PagePdf
    {
        $this->assertNotClosed();

        $pdfOptions = [];

        // is landscape?
        if (\array_key_exists('landscape', $options)) {
            // landscape requires type to be boolean
            if (!\is_bool($options['landscape'])) {
                throw new \InvalidArgumentException('Invalid options "landscape" for print to pdf. Must be true or false');
            }
            $pdfOptions['landscape'] = $options['landscape'];
        }

        // should print background?
        if (\array_key_exists('printBackground', $options)) {
            // printBackground requires type to be boolean
            if (!\is_bool($options['printBackground'])) {
                throw new \InvalidArgumentException('Invalid options "printBackground" for print to pdf. Must be true or false');
            }
            $pdfOptions['printBackground'] = $options['printBackground'];
        }

        // option displayHeaderFooter
        if (\array_key_exists('displayHeaderFooter', $options)) {
            // displayHeaderFooter requires type to be boolean
            if (!\is_bool($options['displayHeaderFooter'])) {
                throw new \InvalidArgumentException('Invalid options "displayHeaderFooter" for print to pdf. Must be true or false');
            }
            $pdfOptions['displayHeaderFooter'] = $options['displayHeaderFooter'];
        }

        // option headerTemplate
        if (\array_key_exists('headerTemplate', $options)) {
            // headerTemplate requires type to be string
            if (!\is_string($options['headerTemplate'])) {
                throw new \InvalidArgumentException('Invalid options "headerTemplate" for print to pdf. Must be string');
            }
            $pdfOptions['headerTemplate'] = $options['headerTemplate'];
        }

        // option footerTemplate
        if (\array_key_exists('footerTemplate', $options)) {
            // footerTemplate requires type to be string
            if (!\is_string($options['footerTemplate'])) {
                throw new \InvalidArgumentException('Invalid options "footerTemplate" for print to pdf. Must be string');
            }
            $pdfOptions['footerTemplate'] = $options['footerTemplate'];
        }

        // option paperWidth
        if (\array_key_exists('paperWidth', $options)) {
            // paperWidth requires type to be float
            if ('double' !== \gettype($options['paperWidth'])) {
                throw new \InvalidArgumentException('Invalid options "paperWidth" for print to pdf. Must be float like 1.0 or 5.4');
            }
            $pdfOptions['paperWidth'] = $options['paperWidth'];
        }

        // option paperHeight
        if (\array_key_exists('paperHeight', $options)) {
            // paperHeight requires type to be float
            if ('double' !== \gettype($options['paperHeight'])) {
                throw new \InvalidArgumentException('Invalid options "paperHeight" for print to pdf. Must be float like 1.0 or 5.4');
            }
            $pdfOptions['paperHeight'] = $options['paperHeight'];
        }

        // option marginTop
        if (\array_key_exists('marginTop', $options)) {
            // marginTop requires type to be float
            if ('double' !== \gettype($options['marginTop'])) {
                throw new \InvalidArgumentException('Invalid options "marginTop" for print to pdf. Must be float like 1.0 or 5.4');
            }
            $pdfOptions['marginTop'] = $options['marginTop'];
        }

        // option marginBottom
        if (\array_key_exists('marginBottom', $options)) {
            // marginBottom requires type to be float
            if ('double' !== \gettype($options['marginBottom'])) {
                throw new \InvalidArgumentException('Invalid options "marginBottom" for print to pdf. Must be float like 1.0 or 5.4');
            }
            $pdfOptions['marginBottom'] = $options['marginBottom'];
        }

        // option marginLeft
        if (\array_key_exists('marginLeft', $options)) {
            // marginLeft requires type to be float
            if ('double' !== \gettype($options['marginLeft'])) {
                throw new \InvalidArgumentException('Invalid options "marginLeft" for print to pdf. Must be float like 1.0 or 5.4');
            }
            $pdfOptions['marginLeft'] = $options['marginLeft'];
        }

        // option marginRight
        if (\array_key_exists('marginRight', $options)) {
            // marginRight requires type to be float
            if ('double' !== \gettype($options['marginRight'])) {
                throw new \InvalidArgumentException('Invalid options "marginRight" for print to pdf. Must be float like 1.0 or 5.4');
            }
            $pdfOptions['marginRight'] = $options['marginRight'];
        }

        // option preferCSSPageSize
        if (\array_key_exists('preferCSSPageSize', $options)) {
            // preferCSSPageSize requires type to be boolean
            if (!\is_bool($options['preferCSSPageSize'])) {
                throw new \InvalidArgumentException('Invalid options "preferCSSPageSize" for print to pdf. Must be true or false');
            }
            $pdfOptions['preferCSSPageSize'] = $options['preferCSSPageSize'];
        }

        if (\array_key_exists('scale', $options)) {
            if (!\is_float($options['scale'])) {
                throw new \InvalidArgumentException('Invalid options "scale" for print to pdf. Must be float like 1.0 or 0.74');
            }
            $pdfOptions['scale'] = $options['scale'];
        }

        // request pdf
        $responseReader = $this->getSession()
            ->sendMessage(new Message('Page.printToPDF', $pdfOptions));

        return new PagePdf($responseReader);
    }

    /**
     * Allows to change viewport size, enabling mobile mode, or changing the scale factor.
     *
     * usage:
     *
     * ```
     * $page->setDeviceMetricsOverride
     * ```
     *
     * @param array $overrides
     *
     * @throws CommunicationException
     * @throws NoResponseAvailable
     *
     * @return ResponseWaiter
     */
    public function setDeviceMetricsOverride(array $overrides)
    {
        if (!\array_key_exists('width', $overrides)) {
            $overrides['width'] = 0;
        }
        if (!\array_key_exists('height', $overrides)) {
            $overrides['height'] = 0;
        }
        if (!\array_key_exists('deviceScaleFactor', $overrides)) {
            $overrides['deviceScaleFactor'] = 0;
        }
        if (!\array_key_exists('mobile', $overrides)) {
            $overrides['mobile'] = false;
        }

        $this->assertNotClosed();

        return new ResponseWaiter($this->getSession()->sendMessage(
            new Message('Emulation.setDeviceMetricsOverride', $overrides)
        ));
    }

    /**
     * Set viewport size.
     *
     * @param int $width
     * @param int $height
     *
     * @throws CommunicationException
     * @throws NoResponseAvailable
     *
     * @return ResponseWaiter
     */
    public function setViewport(int $width, int $height)
    {
        return $this->setDeviceMetricsOverride([
            'width' => $width,
            'height' => $height,
        ]);
    }

    /**
     * Get mouse object to play with.
     *
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
     * Get keyboard object to play with.
     *
     * @return Keyboard
     */
    public function keyboard()
    {
        if (!$this->keyboard) {
            $this->keyboard = new Keyboard($this);
        }

        return $this->keyboard;
    }

    public function dom(): Dom
    {
        return new Dom($this);
    }

    /**
     * Request to close the page.
     *
     * @throws CommunicationException
     */
    public function close(): void
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
     * Throws if the page was closed.
     */
    public function assertNotClosed(): void
    {
        if ($this->target->isDestroyed()) {
            throw new TargetDestroyed('The page was closed and is not available anymore.');
        }
    }

    /**
     * Set user agent for the current page.
     *
     * @see https://source.chromium.org/chromium/chromium/deps/icu.git/+/faee8bc70570192d82d2978a71e2a615788597d1:source/data/misc/metaZones.txt | ICU’s metaZones.txt
     *
     * @throws InvalidTimezoneId|CommunicationException|NoResponseAvailable
     */
    public function setTimezone($timezoneId = null): void
    {
        // ensure target is not closed
        $this->assertNotClosed();

        $response = $this->getSession()
            ->sendMessageSync(
                new Message(
                    'Emulation.setTimezoneOverride',
                    [
                        'timezoneId' => $timezoneId ?? '',
                    ]
                )
            );

        if (\strpos($response->getErrorMessage(), 'Invalid timezone')) {
            throw new InvalidTimezoneId("Invalid Timezone ID: $timezoneId");
        }
    }

    /**
     * Gets the current url of the page, always in sync with the browser.
     *
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     *
     * @return mixed
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
     * Gets the raw html of the current page.
     *
     * @throws Exception\CommunicationException
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->evaluate('document.documentElement.outerHTML')->getReturnValue();
    }

    /**
     * Read cookies for the current page.
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
     * @throws CommunicationException
     * @throws CommunicationException\CannotReadResponse
     * @throws CommunicationException\InvalidResponse
     *
     * @return CookiesGetter
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
                    'urls' => [$this->getCurrentUrl()],
                ]
            )
        );

        // return async helper
        return new CookiesGetter($response);
    }

    /**
     * Read all cookies in the browser.
     *
     * @see getCookies
     * @see readCookies
     * @see getAllCookies
     *
     * ```
     *   $page->readCookies()->await()->getCookies();
     * ```
     *
     * @throws CommunicationException
     *
     * @return CookiesGetter
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
     * Get cookies for the current page synchronously.
     *
     * @see readCookies
     * @see readAllCookies
     * @see getAllCookies
     *
     * @param int|null $timeout
     *
     * @throws CommunicationException
     * @throws Exception\OperationTimedOut
     * @throws NoResponseAvailable
     *
     * @return CookiesCollection
     */
    public function getCookies(int $timeout = null)
    {
        return $this->readCookies()->await($timeout)->getCookies();
    }

    /**
     * Get all browser cookies synchronously.
     *
     * @see getCookies
     * @see readAllCookies
     * @see readCookies
     *
     * @param int|null $timeout
     *
     * @throws CommunicationException
     * @throws Exception\OperationTimedOut
     * @throws NoResponseAvailable
     *
     * @return CookiesCollection
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
                $browserCookie['domain'] = \parse_url($this->getCurrentUrl(), \PHP_URL_HOST);
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
     * Set user agent for the current page.
     *
     * @param string $userAgent
     *
     * @throws CommunicationException
     *
     * @return ResponseWaiter
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
