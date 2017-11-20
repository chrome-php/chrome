<?php
/**
 * @license see LICENSE
 */

namespace HeadlessChromium\Browser;

use HeadlessChromium\Browser;
use HeadlessChromium\Communication\Connection;
use Symfony\Component\Process\Process;

class ProcessAwareBrowser extends Browser
{

    /**
     * @var BrowserProcess
     */
    protected $browserProcess;
    
    public function __construct(Connection $connection, BrowserProcess $browserProcess)
    {
        parent::__construct($connection);

        $this->browserProcess = $browserProcess;
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
        $this->browserProcess->kill();
    }
}
