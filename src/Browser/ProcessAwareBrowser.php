<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium\Browser;

use HeadlessChromium\Browser;
use HeadlessChromium\Communication\Connection;

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
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->browserProcess->kill();
    }

    /**
     * @return string
     */
    public function getSocketUri()
    {
        return $this->browserProcess->getSocketUri();
    }
}
