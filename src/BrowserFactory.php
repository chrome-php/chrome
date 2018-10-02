<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use Apix\Log\Logger\Stream as StreamLogger;
use HeadlessChromium\Browser\BrowserProcess;
use Symfony\Component\Process\Process;

class BrowserFactory
{

    protected $chromeBinaries;

    public function __construct(string $chromeBinaries = null)
    {
        // auto guess chrome binaries
        if (null === $chromeBinaries) {
            $envChromePath = getenv('CHROME_PATH');

            if ($envChromePath) {
                $chromeBinaries = '"' . $envChromePath . '"';
            } else {
                $chromeBinaries = 'chrome';
            }
        }

        $this->chromeBinaries = $chromeBinaries;
    }

    /**
     * Start a chrome process and allows to interact with it
     *
     * @param array $options options for browser creation:
     * - connectionDelay: amount of time in seconds to slows down connection for debugging purposes (default: none)
     * - customFlags: array of custom flag to flags to pass to the command line
     * - debug: toggles the debug mode that allows to print additional details (default: false)
     * - debugLogger: resource string ("php://stdout"), resource or psr-3 logger instance (default: none)
     *   enabling debug logger will also enable debug mode.
     * - enableImages: toggle the loading of images (default: true)
     * - headless: whether chrome should be started headless (default: true)
     * - ignoreCertificateErrors: set chrome to ignore ssl errors
     * - noSandbox: enable no sandbox mode (default: false)
     * - sendSyncDefaultTimeout: maximum time in ms to wait for synchronous messages to send (default 3000 ms)
     * - startupTimeout: maximum time in seconds to wait for chrome to start (default: 30 sec)
     * - userAgent: user agent to use for the browser
     * - userDataDir: chrome user data dir (default: a new empty dir is generated temporarily)
     * - windowSize: size of the window, ex: [1920, 1080] (default: none)
     *
     * @return Browser a Browser instance to interact with the new chrome process
     */
    public function createBrowser(array $options = []): Browser
    {

        // prepare logger
        $logger = $options['debugLogger'] ?? null;

        // create logger from string name or resource
        if (is_string($logger) || is_resource($logger)) {
            $logger = new StreamLogger($logger);
            $options['debug'] = true;
        }

        $debugEnabled = $options['debug'] ?? false;

        // log
        if ($debugEnabled) {
            $chromeVersion = $this->getChromeVersion();

            if ($logger) {
                $logger->debug('Factory: chrome version: ' . $chromeVersion);
            }
        }

        // create browser process
        $browserProcess = new BrowserProcess($logger);

        // instruct the runtime to kill chrome and clean temp files on exit
        register_shutdown_function([$browserProcess, 'kill']);

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
}
