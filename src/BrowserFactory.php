<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Exception\OperationTimedOut;
use Symfony\Component\Filesystem\Filesystem;
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
     *
     * @return Browser a Browser instance to interact with the new chrome process
     */
    public function createBrowser(array $options = []): Browser
    {
        // $killer stores data used to stop browser and clear temporary data on exit
        $killer = new \stdClass();
        register_shutdown_function(function ($killer) {
            $this->killOnExit($killer);
        }, $killer);

        // user-data-dir
        if (!array_key_exists('userDataDir', $options) || !$options['userDataDir']) {
            // if no data dir specified create it
            $options['userDataDir'] = $this->createTempDir();

            // add user data dir to get removed on close
            $killer->dataDir = $options['userDataDir'];
        }

        // get args for command line
        $args = $this->getArgsForOptions($options);

        // start chrome
        $process = new Process($this->chromeBinaries . ' ' . implode(' ', $args));
        $process->start();

        // add $process on $killer to allow killing chrome on exist
        $killer->process = $process;


        // wait for start and retrieve ws uri
        $startupTimeout = $options['startupTimeout'] ?? 30;
        $ws = $this->waitForStartup($process, $startupTimeout * 1000 * 1000);

        // connect to browser
        $connection = new Connection($ws);
        $connection->connect();

        // connection delay
        if (array_key_exists('connectionDelay', $options)) {
            $connection->setConnectionDelay($options['connectionDelay']);
        }

        // set connection to allow kill chrome on exit
        $killer->connection = $connection;

        return new Browser($connection);
    }

    /**
     * Get args for creating chrome's startup command
     * @param array $options
     * @return array
     */
    private function getArgsForOptions(array $options)
    {
        // command line args to add to start chrome (inspired by puppeteer configs)
        // see https://peter.sh/experiments/chromium-command-line-switches/
        $args = [
            // auto debug port
            '--remote-debugging-port=0',

            // disable undesired features
            '--disable-background-networking',
            '--disable-background-timer-throttling',
            '--disable-client-side-phishing-detection',
            '--disable-default-apps',
            '--disable-extensions',
            '--disable-hang-monitor',
            '--disable-popup-blocking',
            '--disable-prompt-on-repost',
            '--disable-sync',
            '--disable-translate',
            '--metrics-recording-only',
            '--no-first-run',
            '--safebrowsing-disable-auto-update',

            // automation mode
            '--enable-automation',

            // password settings
            '--password-store=basic',
            '--use-mock-keychain', // osX only
        ];

        // enable headless mode
        if (!array_key_exists('headless', $options) || $options['headless']) {
            $args[] = '--headless';
            $args[] = '--disable-gpu';
            $args[] = '--hide-scrollbars';
            $args[] = '--mute-audio';
        }

        // add user data dir to args
        $args[] = '--user-data-dir=' . $options['userDataDir'];

        return $args;
    }

    /**
     * Wait for chrome to startup (given a process) and return the ws uri to connect to
     * @param Process $process
     * @param int $timeout
     * @return mixed
     */
    private function waitForStartup(Process $process, int $timeout)
    {
        try {
            $generator = function (Process $process) {
                while (true) {
                    if (!$process->isRunning()) {
                        throw new \RuntimeException('Chrome process stopped before startup completed');
                    }

                    $output = $process->getIncrementalErrorOutput();

                    if ($output) {
                        if (preg_match('#^DevTools listening on (ws://.*)$#', trim($output), $matches)) {
                            return $matches[1];
                        }
                    }

                    // wait for 10ms
                    yield 10 * 1000;
                }
            };
            return Utils::tryWithTimeout($timeout, $generator($process));
        } catch (OperationTimedOut $e) {
            throw new \RuntimeException('Cannot start browser', 0, $e);
        }
    }
    
    /**
     * Creates a temp directory for the app
     * @return string path to the new temp directory
     */
    private function createTempDir()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'chromium-php-');

        unlink($tmpFile);
        mkdir($tmpFile);

        return $tmpFile;
    }

    /**
     * Handler to be called on php exit to kill browser
     * @param $killer
     * @throws OperationTimedOut
     */
    private function killOnExit($killer)
    {
        if (isset($killer->process)) {

            /** @var Process $process */
            $process = $killer->process;

            // close gracefully if connection exists
            if (isset($killer->connection)) {
                /** @var Connection $connection */
                $connection = $killer->connection;

                if ($connection->isConnected()) {
                    try {
                        // TODO check browser.close on chrome 63
                        $r = $connection->sendMessageSync(new Message('Browser.close'));
                        if (!$r->isSuccessful() && $connection->isConnected()) {
                            throw new \Exception('cannot close, Browser.close not supported');
                        }
                    } catch (\Exception $e) {
                        // close all pages if connected
                        $connection->isConnected() && Utils::closeAllPage($connection);
                    }

                    // wait for process to close
                    $generator = function (Process $process) {
                        while ($process->isRunning()) {
                            // wait for 2ms
                            yield 2 * 1000;
                        }
                    };
                    $timeout = 15 * 1000 * 1000; // 15 seconds
                    Utils::tryWithTimeout($timeout, $generator($process));
                }
            }

            // stop process if running
            $process->isRunning() && $process->stop();
        }

        // remove data dir
        if (isset($killer->dataDir)) {
            try {
                $fs = new Filesystem();
                $fs->remove($killer->dataDir);
            } catch (\Exception $e) {
            }
        }
    }
}
