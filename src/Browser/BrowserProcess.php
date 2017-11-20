<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Browser;

use HeadlessChromium\Browser;
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * A browser process starter. Don't use directly, use BrowserFactory instead
 */
class BrowserProcess
{

    /**
     * chrome instance's user data data
     * @var string
     */
    protected $userDataDir;

    /**
     * @var Process
     */
    protected $process;

    /**
     * True if the user data dir is temporary and should be deleted on process closes
     * @var bool
     */
    protected $userDataDirIsTemp;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Browser
     */
    protected $browser;

    /**
     * @var bool
     */
    protected $wasKilled = false;

    /**
     * @var bool
     */
    protected $wasStarted = false;

    /**
     * Starts the browser
     * @param $binaries
     * @param $options
     */
    public function start($binaries, $options)
    {
        if ($this->wasStarted) {
            // cannot start twice because once started this class contains the necessary data to cleanup the browser.
            // starting in again would result in replacing those data.
            throw new \RuntimeException('This process was already started');
        }
        
        $this->wasStarted = true;

        // user data dir
        if (!array_key_exists('userDataDir', $options) || !$options['userDataDir']) {
            // if no data dir specified create it
            $options['userDataDir'] = $this->createTempDir();

            // set user data dir to get removed on close
            $this->userDataDirIsTemp = true;
        }
        $this->userDataDir = $options['userDataDir'];

        // get args for command line
        $args = $this->getArgsFromOptions($options);

        // setup chrome process
        $process = new Process($binaries . ' ' . implode(' ', $args));
        $this->process = $process;
        // and start
        $process->start();

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

        // set connection to allow killing chrome
        $this->connection = $connection;

        $this->browser = new ProcessAwareBrowser($connection, $this);
    }

    /**
     * @return Browser
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * Kills the process and clean temporary files
     * @throws OperationTimedOut
     */
    public function kill()
    {

        if ($this->wasKilled) {
            return;
        }

        $this->wasKilled = true;

        if (isset($this->process)) {
            // close gracefully if connection exists
            if (isset($this->connection)) {
                // if socket connect try graceful close
                if ($this->connection->isConnected()) {
                    // first try to close with Browser.close
                    // if Browser.close is not implemented, try to kill by closing all pages
                    try {
                        // TODO check browser.close on chrome 63
                        $r = $this->connection->sendMessageSync(new Message('Browser.close'));
                        if (!$r->isSuccessful()) {
                            throw new \Exception('cannot close, Browser.close not supported');
                        }
                    } catch (\Exception $e) {
                        // close all pages if connected
                        $this->connection->isConnected() && Utils::closeAllPage($this->connection);
                    }

                    // disconnect socket
                    $this->connection->disconnect();

                    // wait for process to close
                    $generator = function (Process $process) {
                        while ($process->isRunning()) {
                            yield 2 * 1000; // wait for 2ms
                        }
                    };
                    $timeout = 15 * 1000 * 1000; // 15 seconds
                    Utils::tryWithTimeout($timeout, $generator($this->process));
                }
            }

            // stop process if running
            $this->process->isRunning() && $this->process->stop();
        }

        // remove data dir
        if ($this->userDataDirIsTemp && $this->userDataDir) {
            try {
                $fs = new Filesystem();
                $fs->remove($this->userDataDir);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Get args for creating chrome's startup command
     * @param array $options
     * @return array
     */
    private function getArgsFromOptions(array $options)
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
}
