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

    public function __construct(string $chromeBinaries = 'chrome')
    {
        $this->chromeBinaries = $chromeBinaries;
    }

    /**
     * Start a chrome process and allows to interact with it
     *
     * @param array $options options for browser creation:
     * - startupTimeout: maximum time in seconds to wait for chrome to start (default: 30 sec)
     * - headless: whether chrome should be started headless (default: true)
     * - userDataDir: chrome user data dir (default: a new empty dir is generated temporarily)
     * - connectionDelay: amount of time in seconds to slows down connection for debugging purposes (default: none)
     * - enableImages: toggle the loading of images (default: true)
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
        }

        // log
        if ($logger) {
            $chromeVersion = $this->getChromeVersion();
            $logger->debug('Factory: chrome version: ' . $chromeVersion);
        }

        // create process browser process
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
            $message = 'Cannot get chrome version, make sure you provided the correct chrome binaries';
            $message .= ' using (' . $this->chromeBinaries . '). ';
            $message .= trim($process->getErrorOutput());
            throw new \RuntimeException($message);
        }

        return trim($process->getOutput());
    }
}
