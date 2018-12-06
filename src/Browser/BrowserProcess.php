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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Wrench\Exception\SocketException;

/**
 * A browser process starter. Don't use directly, use BrowserFactory instead
 */
class BrowserProcess implements LoggerAwareInterface
{

    use LoggerAwareTrait;

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
     * @var string
     */
    protected $wsUri;

    /**
     * BrowserProcess constructor.
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {

        // set or create logger
        $this->setLogger(isset($logger) ? $logger : new NullLogger());
    }

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

        // log
        $this->logger->debug('process: initializing');

        // user data dir
        if (!array_key_exists('userDataDir', $options) || !$options['userDataDir']) {
            // if no data dir specified create it
            $options['userDataDir'] = $this->createTempDir();

            // set user data dir to get removed on close
            $this->userDataDirIsTemp = true;
        }
        $this->userDataDir = $options['userDataDir'];

        // log
        $this->logger->debug('process: using directory: ' . $options['userDataDir']);

        // get args for command line
        $args = $this->getArgsFromOptions($options);

        // process string
        $processString = $binaries . ' ' . implode(' ', $args);

        // log
        $this->logger->debug('process: starting process: ' . $processString);

        // setup chrome process
        $process = new Process($processString);
        $this->process = $process;
        // and start
        $process->start();

        // wait for start and retrieve ws uri
        $startupTimeout = isset($options['startupTimeout']) ? $options['startupTimeout'] : 30;
        $this->wsUri = $this->waitForStartup($process, $startupTimeout * 1000 * 1000);

        // log
        $this->logger->debug('process: connecting using ' . $this->wsUri);

        // connect to browser
        $connection = new Connection($this->wsUri, $this->logger, isset($options['sendSyncDefaultTimeout']) ? $options['sendSyncDefaultTimeout'] : 3000);
        $connection->connect();

        // connection delay
        if (array_key_exists('connectionDelay', $options)) {
            $connection->setConnectionDelay($options['connectionDelay']);
        }

        // set connection to allow killing chrome
        $this->connection = $connection;

        // create browser instance
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
     * @return string
     */
    public function getSocketUri()
    {
        return $this->wsUri;
    }

    /**
     * Kills the process and clean temporary files
     * @throws OperationTimedOut
     */
    public function kill()
    {

        // log
        $this->logger->debug('process: killing chrome');

        if ($this->wasKilled) {
            // log
            $this->logger->debug('process: chrome already killed, ignoring');
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
                        // log
                        $this->logger->debug('process: trying to close chrome gracefully');

                        // TODO check browser.close on chrome 63
                        $r = $this->connection->sendMessageSync(new Message('Browser.close'));
                        if (!$r->isSuccessful()) {
                            // log
                            $this->logger->debug('process: ✗ could not close gracefully');
                            throw new \Exception('cannot close, Browser.close not supported');
                        }
                    } catch (\Exception $e) {
                        //log
                        $this->logger->debug('process: closing chrome gracefully - compatibility');

                        // close all pages if connected
                        $this->connection->isConnected() && Utils::closeAllPage($this->connection);
                    }

                    // disconnect socket
                    try {
                        $this->connection->disconnect();
                    } catch (SocketException $e) {
                        // Socket might be already disconnected
                    }

                    // log
                    $this->logger->debug('process: waiting for process to close');

                    // wait for process to close
                    $generator = function (Process $process) {
                        while ($process->isRunning()) {
                            yield 0 => 2 * 1000; // wait for 2ms
                        }
                    };
                    $timeout = 8 * 1000 * 1000; // 8 seconds

                    try {
                        Utils::tryWithTimeout($timeout, $generator($this->process));
                    } catch (OperationTimedOut $e) {
                        // log
                        $this->logger->debug('process: process didn\'t close by itself');
                    }
                }
            }

            // stop process if running
            if ($this->process->isRunning()) {
                // log
                $this->logger->debug('process: stopping process');

                // stop process
                $exitCode = $this->process->stop();

                // log
                $this->logger->debug('process: process stopped with exit code ' . $exitCode);
            }
        }

        // remove data dir
        if ($this->userDataDirIsTemp && $this->userDataDir) {
            try {
                // log
                $this->logger->debug('process: cleaning temporary resources:' . $this->userDataDir);

                // cleaning
                $fs = new Filesystem();
                $fs->remove($this->userDataDir);
            } catch (\Exception $e) {
                // log
                $this->logger->debug('process: ✗ could not clean temporary resources');
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

        // disable loading of images (currently can't be done via devtools, only CLI)
        if (array_key_exists('enableImages', $options) && ($options['enableImages'] === false)) {
            $args[] = '--blink-settings=imagesEnabled=false';
        }

        // window's size
        if (array_key_exists('windowSize', $options) && $options['windowSize']) {
            if (!is_array($options['windowSize']) ||
                count($options['windowSize']) !== 2 ||
                !is_numeric($options['windowSize'][0]) ||
                !is_numeric($options['windowSize'][1])
            ) {
                throw new \InvalidArgumentException(
                    'Option "windowSize" must be an array of dimensions (eg: [1000, 1200])'
                );
            }

            $args[] = '--window-size=' . implode(',', $options['windowSize']) ;
        }

        // sandbox mode - useful if you want to use chrome headless inside docker
        if (array_key_exists('noSandbox', $options) && $options['noSandbox']) {
            $args[] = '--no-sandbox';
        }

        // user agent
        if (array_key_exists('userAgent', $options)) {
            $args[] = '--user-agent=' . escapeshellarg($options['userAgent']);
        }

        // ignore certificate errors
        if (array_key_exists('ignoreCertificateErrors', $options) && $options['ignoreCertificateErrors']) {
            $args[] = '--ignore-certificate-errors';
        }

        // add custom flags
        if (array_key_exists('customFlags', $options) && is_array($options['customFlags'])) {
            $args =  array_merge($args, $options['customFlags']);
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
    private function waitForStartup(Process $process, $timeout)
    {
        // log
        $this->logger->debug('process: waiting for ' . $timeout / 1000000 . ' seconds for startup');

        try {
            $generator = function (Process $process) {
                while (true) {
                    if (!$process->isRunning()) {
                        // log
                        $this->logger->debug('process: ✗ chrome process stopped');

                        // exception
                        $message = 'Chrome process stopped before startup completed.';
                        $error = trim($process->getErrorOutput());
                        if (!empty($error)) {
                            $message .= ' Additional info: ' . $error;
                        }
                        throw new \RuntimeException($message);
                    }

                    $output = trim($process->getIncrementalErrorOutput());

                    if ($output) {
                        // log
                        $this->logger->debug('process: chrome output:' . $output);

                        $outputs = explode(PHP_EOL, $output);

                        foreach ($outputs as $output) {
                            $output = trim($output);

                            // ignore empty line
                            if (empty($output)) {
                                continue;
                            }

                            // find socket uri
                            if (preg_match('/DevTools listening on (ws:\/\/.*)/', $output, $matches)) {
                                // log
                                $this->logger->debug('process: ✓ accepted output');
                                break;
                            } else {
                                // log
                                $this->logger->debug('process: ignoring output:' . trim($output));
                            }
                        }
                    }

                    // wait for 10ms
                    yield 0 => 10 * 1000;
                }
                if(!isset($matches[1])) {
                    throw new \RuntimeException('Cannot start browser: matches not found');
                }
                yield 1 => $matches[1];
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
