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

use Apix\Log\Logger\Stream as StreamLogger;
use HeadlessChromium\Browser\BrowserProcess;
use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Exception\BrowserConnectionFailed;
use Symfony\Component\Process\Process;
use Wrench\Exception\HandshakeException;

class BrowserFactory
{
    protected $chromeBinary;

    /**
     * Options for browser creation
     *
     * - connectionDelay: amount of time in seconds to slows down connection for debugging purposes (default: none)
     * - customFlags: array of custom flag to flags to pass to the command line
     * - debugLogger: resource string ("php://stdout"), resource or psr-3 logger instance (default: none)
     * - enableImages: toggle the loading of images (default: true)
     * - envVariables: array of environment variables to pass to the process (example DISPLAY variable)
     * - headless: whether chrome should be started headless (default: true)
     * - ignoreCertificateErrors: set chrome to ignore ssl errors
     * - keepAlive: true to keep alive the chrome instance when the script terminates (default: false)
     * - noSandbox: enable no sandbox mode (default: false)
     * - proxyServer: a proxy server, ex: 127.0.0.1:8080 (default: none)
     * - sendSyncDefaultTimeout: maximum time in ms to wait for synchronous messages to send (default 5000 ms)
     * - startupTimeout: maximum time in seconds to wait for chrome to start (default: 30 sec)
     * - userAgent: user agent to use for the browser
     * - userDataDir: chrome user data dir (default: a new empty dir is generated temporarily)
     * - windowSize: size of the window, ex: [1920, 1080] (default: none)
     * - headers: set an array of custom HTTP headers
     */
    protected $options = [];

    public function __construct(string $chromeBinary = null)
    {
        $this->chromeBinary = $chromeBinary ?? (new AutoDiscover())->guessChromeBinaryPath();
    }

    /**
     * Start a chrome process and allows to interact with it.
     *
     * @see BrowserFactory::$options
     *
     * @param array $options overwrite options for browser creation
     *
     * @return ProcessAwareBrowser a Browser instance to interact with the new chrome process
     */
    public function createBrowser(?array $options = null): ProcessAwareBrowser
    {
        // create logger from options
        $logger = self::createLogger($options);

        // create browser process
        $browserProcess = new BrowserProcess($logger);

        if (null === $options) {
            $options = $this->options;
        }

        // instruct the runtime to kill chrome and clean temp files on exit
        if (!\array_key_exists('keepAlive', $options) || !$options['keepAlive']) {
            \register_shutdown_function([$browserProcess, 'kill']);
        }

        // start the browser and connect to it
        $browserProcess->start($this->chromeBinary, $options);

        return $browserProcess->getBrowser();
    }

    public function addHeader(string $name, string $value): void
    {
        $this->options['headers'][$name] = $value;
    }

    /**
     * @param array<string, string> $headers
     */
    public function addHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }
    }

    /**
     * Connects to an existing browser using it's web socket uri.
     *
     * usage:
     *
     * ```
     * $browserFactory = new BrowserFactory();
     * $browser = $browserFactory->createBrowser();
     *
     * $uri = $browser->getSocketUri();
     *
     * $existingBrowser = BrowserFactory::connectToBrowser($uri);
     * ```
     *
     * @param string $uri
     * @param array  $options options when creating the connection to the browser:
     *                        - connectionDelay: amount of time in seconds to slows down connection for debugging purposes (default: none)
     *                        - debugLogger: resource string ("php://stdout"), resource or psr-3 logger instance (default: none)
     *                        - sendSyncDefaultTimeout: maximum time in ms to wait for synchronous messages to send (default 5000 ms)
     *
     * @throws BrowserConnectionFailed
     *
     * @return Browser
     */
    public static function connectToBrowser(string $uri, array $options = []): Browser
    {
        $logger = self::createLogger($options);

        if ($logger) {
            $logger->debug('Browser Factory: connecting using '.$uri);
        }

        // connect to browser
        $connection = new Connection($uri, $logger, $options['sendSyncDefaultTimeout'] ?? 5000);

        // try to connect
        try {
            $connection->connect();
        } catch (HandshakeException $e) {
            throw new BrowserConnectionFailed('Invalid socket uri', 0, $e);
        }

        // make sure it is connected
        if (!$connection->isConnected()) {
            throw new BrowserConnectionFailed('Cannot connect to the browser, make sure it was not closed');
        }

        // connection delay
        if (\array_key_exists('connectionDelay', $options)) {
            $connection->setConnectionDelay($options['connectionDelay']);
        }

        return new Browser($connection);
    }

    /**
     * Set default options to be used in all browser instances
     *
     * @see BrowserFactory::$options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Add or overwrite options to the default options list
     *
     * @see BrowserFactory::$options
     */
    public function addOptions(array $options): void
    {
        $this->options = \array_merge($this->options, $options);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Create a logger instance from given options.
     *
     * @param array $options
     *
     * @return StreamLogger|null
     */
    private static function createLogger($options)
    {
        // prepare logger
        $logger = $options['debugLogger'] ?? null;

        // create logger from string name or resource
        if (\is_string($logger) || \is_resource($logger)) {
            $logger = new StreamLogger($logger);
        }

        return $logger;
    }
}
