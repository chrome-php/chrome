<?php

declare(strict_types=1);

/*
 * This exemple shows how to share a single instance of chrome for multiple scripts.
 *
 * The first time the script is started we use the browser factory in order to start chrome,
 * afterwhat we save the uri to connect to this browser in the file system.
 *
 * Next calls to the script will read the uri from that file in order to connect to the chrome instance instead
 * of creating a new one. If chrome was closed or crashed, a new instance is started again.
 */

require __DIR__.'/../vendor/autoload.php';

// path to the file to store websocket's uri
$socketFile = '/tmp/chrome-php-demo-socket';

// initialize $browser variable
$browser = null;

// try to connect to chrome instance if it exists
if (\file_exists($socketFile)) {
    $socket = \file_get_contents($socketFile);

    try {
        $browser = \HeadlessChromium\BrowserFactory::connectToBrowser($socket, [
            'debugLogger' => 'php://stdout',
        ]);
    } catch (\HeadlessChromium\Exception\BrowserConnectionFailed $e) {
        // The browser was probably closed
        // Keep $browser null and start it again bellow
    }
}

// if $browser is null then create a new chrome instance
if (!$browser) {
    $factory = new \HeadlessChromium\BrowserFactory();
    $browser = $factory->createBrowser([
        'headless' => false,
        'keepAlive' => true,
    ]);

    // save the uri to be able to connect again to browser
    \file_put_contents($socketFile, $browser->getSocketUri());
}

// do something with the browser
$page = $browser->createPage();

$page->navigate('http://example.com')->waitForNavigation();
