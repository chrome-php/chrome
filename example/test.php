<?php
ini_set('xdebug.max_nesting_level', 300);
require __DIR__ . '/../vendor/autoload.php';

use HeadlessChromium\BrowserFactory;

$time_start = microtime(true);

$browserOptions = [
    'keepAlive'       => true,          //don't close browser after end
                                        //NOTE: if browser autoclosing - you need to create blank page & leave it
    'headless'        => false,         // disable headless mode
    'connectionDelay' => 0.8,           // add 0.8 second of delay between each instruction sent to chrome,
    'debugLogger'     => 'php://stdout' // will enable verbose mode
];
$browser = BrowserFactory::getBrowser($browserOptions);
$url = 'https://instagram.com/';

$page = $browser->createPage();
$contentGetMethod = $page->getContent($url);
$page->close();
var_dump($contentGetMethod);

$page = $browser->createPage();
$contentPostMethod = $page->getContentPost($url);
$page->close();
var_dump($contentPostMethod);

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
echo PHP_EOL.PHP_EOL.'Total execution Time: '.$execution_time.' seconds'.PHP_EOL.PHP_EOL;