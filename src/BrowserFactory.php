<?php
/**
 * @license see LICENSE
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

    const SOCKET_FILE = '/tmp/chrome-php-socket';

    protected $chromeBinaries;

    public function __construct($chromeBinaries = null)
    {
        // auto guess chrome binaries
        if (null === $chromeBinaries) {
            $envChromePath = getenv('CHROME_PATH');

            if ($envChromePath) {
                $chromeBinaries = '"' . $envChromePath . '"';
            } else {
                $chromeBinaries = 'google-chrome-stable';
            }
        }

        $this->chromeBinaries = $chromeBinaries;
    }

    /**
     * @param array $options
     * @return Browser|ProcessAwareBrowser|null
     */
    public function getBrowser(array $options = [])
    {
        $browser = null;
        if (file_exists(self::SOCKET_FILE)) {
            $socket =  file_get_contents(self::SOCKET_FILE);
            try {
                $browser = self::connectToBrowser($socket, $options);
            } catch (\HeadlessChromium\Exception\BrowserConnectionFailed $e) {
                $logger = self::createLogger($options);
                if($logger) {
                    $logger->debug('Browser is closed, opening new instance ');
                }
            }
        }
        if (!$browser) {
            $browserFactory = new BrowserFactory();
            $browser = $browserFactory->createBrowser($options);
            file_put_contents(self::SOCKET_FILE, $browser->getSocketUri());
        }

        return $browser;
    }

    /**
     * Start a chrome process and allows to interact with it
     *
     * @param array $options options for browser creation:
     * - connectionDelay: amount of time in seconds to slows down connection for debugging purposes (default: none)
     * - customFlags: array of custom flag to flags to pass to the command line
     * - debugLogger: resource string ("php://stdout"), resource or psr-3 logger instance (default: none)
     * - enableImages: toggle the loading of images (default: true)
     * - headless: whether chrome should be started headless (default: true)
     * - ignoreCertificateErrors: set chrome to ignore ssl errors
     * - keepAlive: true to keep alive the chrome instance when the script terminates (default: false)
     * - noSandbox: enable no sandbox mode (default: false)
     * - sendSyncDefaultTimeout: maximum time in ms to wait for synchronous messages to send (default 3000 ms)
     * - startupTimeout: maximum time in seconds to wait for chrome to start (default: 30 sec)
     * - userAgent: user agent to use for the browser
     * - userDataDir: chrome user data dir (default: a new empty dir is generated temporarily)
     * - windowSize: size of the window, ex: [1920, 1080] (default: none)
     *
     * @return ProcessAwareBrowser a Browser instance to interact with the new chrome process
     */
    public function createBrowser(array $options = [])
    {

        // create logger from options
        $logger = self::createLogger($options);

        // log chrome version
        if ($logger) {
            $chromeVersion = $this->getChromeVersion();
            $logger->debug('Factory: chrome version: ' . $chromeVersion);
        }

        // create browser process
        $browserProcess = new BrowserProcess($logger);

        // instruct the runtime to kill chrome and clean temp files on exit
        if (!array_key_exists('keepAlive', $options) || !$options['keepAlive']) {
            register_shutdown_function([$browserProcess, 'kill']);
        }

        // start the browser and connect to it
        $browserProcess->start($this->chromeBinaries, $options);

        return $browserProcess->getBrowser();
    }

    /**
     * Get chrome version
     * @return string
     */
    public function getChromeVersion()
    {
        $process = new Process($this->chromeBinaries . ' --version');

        $exitCode = $process->run();

        if ($exitCode != 0) {
            $message = 'Cannot read chrome version, make sure you provided the correct chrome executable';
            $message .= ' using: "' . $this->chromeBinaries . '". ';

            $error = trim($process->getErrorOutput());

            if (!empty($error)) {
                $message .= 'Additional info: ' . $error;
            }
            throw new \RuntimeException($message);
        }

        return trim($process->getOutput());
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
     * @param array $options options when creating the connection to the browser:
     *  - connectionDelay: amount of time in seconds to slows down connection for debugging purposes (default: none)
     *  - debugLogger: resource string ("php://stdout"), resource or psr-3 logger instance (default: none)
     *  - sendSyncDefaultTimeout: maximum time in ms to wait for synchronous messages to send (default 3000 ms)
     *
     * @return Browser
     * @throws BrowserConnectionFailed
     */
    public static function connectToBrowser($uri, array $options = [])
    {
        $logger = self::createLogger($options);

        if ($logger) {
            $logger->debug('Browser Factory: connecting using ' . $uri);
        }

        // connect to browser
        $connection = new Connection($uri, $logger, isset($options['sendSyncDefaultTimeout']) ? $options['sendSyncDefaultTimeout'] : 3000);

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
        if (array_key_exists('connectionDelay', $options)) {
            $connection->setConnectionDelay($options['connectionDelay']);
        }

        return new Browser($connection);
    }

    /**
     * Create a logger instance from given options
     * @param $options
     * @return StreamLogger|null
     */
    private static function createLogger($options)
    {
        // prepare logger
        $logger = isset($options['debugLogger']) ? $options['debugLogger'] : null;

        // create logger from string name or resource
        if (is_string($logger) || is_resource($logger)) {
            $logger = new StreamLogger($logger);
        }

        return $logger;
    }
}
