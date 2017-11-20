<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium;

use HeadlessChromium\Browser\BrowserProcess;

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
        $browserProcess = new BrowserProcess();

        // kill chrome and clean temp files on exit
        register_shutdown_function([$browserProcess, 'kill']);
        
        $browserProcess->start($this->chromeBinaries, $options);
        
        return $browserProcess->getBrowser();
    }
}
