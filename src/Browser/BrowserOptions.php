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

abstract class BrowserOptions
{
    /**
     * Delay to apply between each operation for debugging purposes.
     */
    public const connectionDelay = 'connectionDelay';

    /**
     * An array of flags to pass to the command line.
     */
    public const customFlags = 'customFlags';

    /**
     * A string (e.g "php://stdout"), or resource, or PSR-3 logger instance to print debug messages.
     */
    public const debugLogger = 'debugLogger';

    /**
     * Toggles loading of images.
     *
     * Default: true
     */
    public const enableImages = 'enableImages';

    /**
     * An array of environment variables to pass to the process.
     *
     * Example: DISPLAY
     */
    public const envVariables = 'envVariables';

    /**
     * An array of custom HTTP headers.
     */
    public const headers = 'headers';

    /**
     * Enable or disable headless mode.
     *
     * Default: true
     */
    public const headless = 'headless';

    /**
     * Set chrome to ignore ssl errors.
     */
    public const ignoreCertificateErrors = 'ignoreCertificateErrors';

    /**
     * Whether or not to keep alive the chrome instance when the script terminates.
     *
     * Default: false
     */
    public const keepAlive = 'keepAlive';

    /**
     * Enable no sandbox mode, useful to run in a docker container.
     *
     * Default: false
     */
    public const noSandbox = 'noSandbox';

    /**
     * Proxy server to use.
     *
     * Example: 127.0.0.1:8080
     */
    public const proxyServer = 'proxyServer';

    /**
     * Default timeout in milliseconds for sending sync messages.
     *
     * Default: 5000
     */
    public const sendSyncDefaultTimeout = 'sendSyncDefaultTimeout';

    /**
     * Maximum time in seconds to wait for chrome to start.
     *
     * Default: 30
     */
    public const startupTimeout = 'startupTimeout';

    /**
     * User agent to use for the whole browser.
     */
    public const userAgent = 'userAgent';

    /**
     * Chrome user data dir.
     *
     * Default: a new empty dir is generated temporarily
     */
    public const userDataDir = 'userDataDir';

    /**
     * Size of the window.
     *
     * Example: [1920, 1080]
     */
    public const windowSize = 'windowSize';
}
