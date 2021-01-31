Chrome PHP
==========

[![Latest Stable Version](https://poser.pugx.org/chrome-php/chrome/version)](https://packagist.org/packages/chrome-php/chrome)
[![License](https://poser.pugx.org/chrome-php/chrome/license)](https://packagist.org/packages/chrome-php/chrome)

This library lets you start playing with chrome/chromium in headless mode from PHP.

Can be used synchronously and asynchronously!

⚠️Looking for maintainers⚠️
---------------------------

We're looking for some permanent maintainers to help with maintaining this library.

It can be for handling issues, adding documentation, adding new features, fixing bugs, etc...

Features
--------

- Open chrome or chromium browser from php
- Create pages and navigate to pages
- Take screenshots
- Evaluate javascript in the page
- Make PDF
- Emulate mouse 
- Emulate keyboard 
- Always IDE friendly

Happy browsing!

Requirements
------------

Requires PHP 7.1+ and a chrome/chromium executable. 

As of version 65 of chrome/chromium the library proved to work correctly. 
Please try to keep using latest version of chrome.

Note that the library is only tested on linux but is compatible with osX and windows.

Install
-------

The library can be installed with composer and is available on packagist under [chrome-php/chrome](https://packagist.org/packages/chrome-php/chrome)

```bash
$ composer require chrome-php/chrome
```

Usage
-----

It uses a simple and understandable API to start chrome, to open pages, to take screenshots, 
to crawl websites... and almost everything that you can do with chrome as a human.

```php
use HeadlessChromium\BrowserFactory;

$browserFactory = new BrowserFactory();

// starts headless chrome
$browser = $browserFactory->createBrowser();

try {
    // creates a new page and navigate to an url
    $page = $browser->createPage();
    $page->navigate('http://example.com')->waitForNavigation();

    // get page title
    $pageTitle = $page->evaluate('document.title')->getReturnValue();

    // screenshot - Say "Cheese"! 😄
    $page->screenshot()->saveToFile('/foo/bar.png');

    // pdf
    $page->pdf(['printBackground' => false])->saveToFile('/foo/bar.pdf');
} finally {
    // bye
    $browser->close();
}
```

### Using different chrome executable

When starting the factory will look for the environment variable ``"CHROME_PATH"`` to find the chrome executable.
If the variable is not found then it will use ``"chrome"`` as the executable.

You can use any executable of your choice. For instance ``"chromium-browser"``:

```php
use HeadlessChromium\BrowserFactory;

// replace default 'chrome' with 'chromium-browser'
$browserFactory = new BrowserFactory('chromium-browser');
```

### Debugging 

The following example disables headless mode to ease debugging 

```php
use HeadlessChromium\BrowserFactory;

$browserFactory = new BrowserFactory();

$browser = $browserFactory->createBrowser([
    'headless'        => false,          // disable headless mode
]);
```

Other debug options:

```php
[
    'connectionDelay' => 0.8,            // add 0.8 second of delay between each instruction sent to chrome,
    'debugLogger'     => 'php://stdout', // will enable verbose mode
]
```

About ``debugLogger``: this can be any of a resource string, a resource or an object implementing 
``LoggerInterface`` from Psr\Log (such as [monolog](https://github.com/Seldaek/monolog) 
or [apix/log](https://github.com/apix/log)).

API
---

### Browser Factory

```php
use HeadlessChromium\BrowserFactory;

$browserFactory = new BrowserFactory();
$browser = $browserFactory->createBrowser([
    'windowSize'      => [1920, 1000],
    'enableImages'    => false,
]);
```

#### Options

Here are the options available for the browser factory:

| Option name               | Default | Description                                                                                  |
|---------------------------|---------|----------------------------------------------------------------------------------------------|
| `connectionDelay`         | `0`     | Delay to apply between each operation for debugging purposes                                 |
| `customFlags`             | none    | Array of flags to pass to the command line. Eg: `['--option1', '--option2=someValue']`       |
| `debugLogger`             | `null`  | A string (e.g "php://stdout"), or resource, or PSR-3 logger instance to print debug messages |
| `enableImages`            | `true`  | Toggles loading of images                                                                    |
| `headless`                | `true`  | Enable or disable headless mode                                                              |
| `ignoreCertificateErrors` | `false` | Set chrome to ignore ssl errors                                                              |
| `keepAlive`               | `false` | true to keep alive the chrome instance when the script terminates                            |
| `noSandbox`               | `false` | Useful to run in a docker container                                                          |
| `sendSyncDefaultTimeout`  | `5000`  | Default timeout (ms) for sending sync messages                                               |
| `startupTimeout`          | `30`    | Maximum time in seconds to wait for chrome to start                                          |
| `userAgent`               | none    | User agent to use for the whole browser  (see page api for alternative)                      |
| `userDataDir`             | none    | chrome user data dir (default: a new empty dir is generated temporarily)                     |
| `windowSize`              | none    | Size of the window. usage: `$width, $height` - see also Page::setViewport                  |

### Browser API

#### Create a new page (tab)

```php
$page = $browser->createPage();

// destination can be specified
$uri = 'http://example.com';
$page = $browser->createPage($uri);
```

#### Close the browser

```php
    $browser->close();
```

### Set a script to evaluate before every page created by this browser will navigate

```php
$browser->setPagePreScript('// Simulate navigator permissions;
const originalQuery = window.navigator.permissions.query;
window.navigator.permissions.query = (parameters) => (
    parameters.name === 'notifications' ?
        Promise.resolve({ state: Notification.permission }) :
        originalQuery(parameters)
);');
```

### Page API

#### Navigate to an url

```php
// navigate
$navigation = $page->navigate('http://example.com');

// wait for the page to be loaded
$navigation->waitForNavigation();
```

When Using ``$navigation->waitForNavigation()`` you will wait for 30sec until the page event "loaded" is triggered.
You can change the timeout or the event to listen for:

```php
// wait 10secs for the event "DOMContentLoaded" to be triggered
$navigation->waitForNavigation(Page::DOM_CONTENT_LOADED, 10000);
```

Available events (in the order they trigger):

- ``Page::DOM_CONTENT_LOADED``: dom has completely loaded
- ``Page::LOAD``: (default) page and all resources are loaded
- ``Page::NETWORK_IDLE``: page has loaded, and no network activity has occurred for at least 500ms

When you want to wait for the page to navigate there are 2 main issues that may occur. 
First the page is too long to load and second the page you were waiting to be loaded has been replaced.
The good news is that you can handle those issues using a good old try catch:

```php
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Exception\NavigationExpired;

try {
    $navigation->waitForNavigation()
} catch (OperationTimedOut $e) {
    // too long to load
} catch (NavigationExpired $e) {
    // An other page was loaded
}
```

#### Evaluate script on the page

Once the page has completed the navigation you can evaluate arbitrary script on this page:

```php
// navigate
$navigation = $page->navigate('http://example.com');
    
// wait for the page to be loaded
$navigation->waitForNavigation();

// evaluate script in the browser
$evaluation = $page->evaluate('document.documentElement.innerHTML');

// wait for the value to return and get it
$value = $evaluation->getReturnValue();
```


Sometime the script you evaluate will click a link or submit a form, in this case the page will reload and you
will want to wait for the new page to reload.

You can achieve this by using ``$page->evaluate('some js that will reload the page')->waitForPageReload()``.
An example is available in [form-submit.php](./examples/form-submit.php)

#### Call a function

This is an alternative to ``evaluate`` that allows to call a given function with the given arguments in the page context:

```php
$evaluation = $page->callFunction(
    "function(a, b) {\n    window.foo = a + b;\n}", 
    [1, 2]
);

$value = $evaluation->getReturnValue();
```

#### Add a script tag

That's useful if you want to add jQuery (or anything else) to the page:

```php
$page->addScriptTag([
    'content' => file_get_contents('path/to/jquery.js')
])->waitForResponse();

$page->evaluate('$(".my.element").html()');
```

You can also use an url to feed the src attribute:

```php
$page->addScriptTag([
    'url' => 'https://code.jquery.com/jquery-3.3.1.min.js'
])->waitForResponse();

$page->evaluate('$(".my.element").html()');
```

#### Get the page HTML

You can get the page HTML as a string using the ```getHtml``` method.

```php
$html = $page->getHtml();
```

### Add a script to evaluate upon page navigation

```php
$page->addPreScript('// Simulate navigator permissions;
const originalQuery = window.navigator.permissions.query;
window.navigator.permissions.query = (parameters) => (
    parameters.name === 'notifications' ?
        Promise.resolve({ state: Notification.permission }) :
        originalQuery(parameters)
);');
```

If your script needs the dom to be fully populated before it runs then you can use the option "onLoad":

```php
$page->addPreScript($script, ['onLoad' => true]);
```

#### Set viewport size

This features allows to change the size of the viewport (emulation) for the current page without affecting the size of 
all the browser's pages (see also option ``"windowSize"`` of [BrowserFactory::createBrowser](#options)).

```php
$width = 600;
$height = 300;
$page->setViewport($width, $height)
    ->await(); // wait for operation to complete
```

#### Make a screenshot

```php
// navigate
$navigation = $page->navigate('http://example.com');
    
// wait for the page to be loaded
$navigation->waitForNavigation();

// take a screenshot
$screenshot = $page->screenshot([
    'format'  => 'jpeg',  // default to 'png' - possible values: 'png', 'jpeg',
    'quality' => 80,      // only if format is 'jpeg' - default 100 
]);

// save the screenshot
$screenshot->saveToFile('/some/place/file.jpg');
```

**choose an area**

You can use the option "clip" in order to choose an area for the screenshot (TODO exemple)

**take a full page screenshot**

You can also take a screenshot for the full layout (not only the layout) using ``$page->getFullPageClip`` (TODO exemple)

TODO ``Page.getFullPageClip();``

```php
use HeadlessChromium\Clip;

// navigate
$navigation = $page->navigate('http://example.com');
    
// wait for the page to be loaded
$navigation->waitForNavigation();

// create a rectangle by specifying to left corner coordinates + width and height
$x = 10;
$y = 10;
$width = 100;
$height = 100;
$clip = new Clip($x, $y, $width, $height);

// take the screenshot (in memory binaries)
$screenshot = $page->screenshot([
    'clip'  => $clip,
]);

// save the screenshot
$screenshot->saveToFile('/some/place/file.jpg');
```

#### Print as PDF

```php
// navigate
$navigation = $page->navigate('http://example.com');
    
// wait for the page to be loaded
$navigation->waitForNavigation();

$options = [
    'landscape'           => true,             // default to false
    'printBackground'     => true,             // default to false
    'displayHeaderFooter' => true,             // default to false
    'preferCSSPageSize'   => true,             // default to false ( reads parameters directly from @page )
    'marginTop'           => 0.0,              // defaults to ~0.4 (must be float, value in inches)
    'marginBottom'        => 1.4,              // defaults to ~0.4 (must be float, value in inches)
    'marginLeft'          => 5.0,              // defaults to ~0.4 (must be float, value in inches)
    'marginRight'         => 1.0,              // defaults to ~0.4 (must be float, value in inches)
    'paperWidth'          => 6.0,              // defaults to 8.5 (must be float, value in inches)
    'paperHeight'         => 6.0,              // defaults to 8.5 (must be float, value in inches)
    'headerTemplate'      => '<div>foo</div>', // see details above
    'footerTemplate'      => '<div>foo</div>', // see details above
    'scale'               => 1.2,              // defaults to 1.0 (must be float)
];

// print as pdf (in memory binaries)
$pdf = $page->pdf($options);

// save the pdf
$pdf->saveToFile('/some/place/file.pdf');

// or directly output pdf without saving
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename=filename.pdf');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

echo base64_decode($pdf->getBase64());
```

Options `headerTempalte` and `footerTempalte`: 

Should be valid HTML markup with following classes used to inject printing values into them:
- date: formatted print date
- title: document title
- url: document location
- pageNumber: current page number
- totalPages: total pages in the document

### Mouse API

The mouse API is dependent on the page instance and allows you to control the mouse's moves, clicks and scroll.

```php
$page->mouse()
    ->move(10, 20)                             // Moves mouse to position x=10;y=20
    ->click()                                  // left click on position set above
    ->move(100, 200, ['steps' => 5])           // move mouse to x=100;y=200 in 5 equal steps
    ->click(['button' => Mouse::BUTTON_RIGHT]; // right click on position set above
    
// given the last click was on a link, the next step will wait
// for the page to load after the link was clicked
$page->waitForReload();
```

You can emulate the mouse wheel to scroll up and down in a page, frame or element.

```php
$page->mouse()
    ->scrollDown(100) // scroll down 100px
    ->scrollUp(50);   // scroll up 50px
```

### Keyboard API

The keyboard API is dependent on the page instance and allows you to type like a real user.

```php
$page->keyboard()
    ->typeRawKey('Tab') // type a raw key, such as Tab
    ->typeText('bar');  // type the text "bar"
```

To impersonate a real user you may want to add a delay between each keystroke using the ```setKeyInterval``` method:

```php
$page->keyboard()->setKeyInterval(10); // sets a delay of 10 miliseconds between keystrokes
```

### Cookie API

You can set and get cookies for a page:

#### Set Cookie

```php
use HeadlessChromium\Cookies\Cookie;

$page = $browser->createPage();

// example 1: set cookies for a given domain

$page->setCookies([
    Cookie::create('name', 'value', [
        'domain' => 'example.com',
        'expires' => time() + 3600 // expires in 1 day
    ])
])->await();


// example 2: set cookies for the current page

$page->navigate('http://example.com')->waitForNavigation();

$page->setCookies([
    Cookie::create('name', 'value', ['expires'])
])->await();
```

#### Get Cookies

```php
use HeadlessChromium\Cookies\Cookie;

$page = $browser->createPage();

// example 1: get all cookies for the browser

$cookies = $page->getAllCookies();

// example 2: get cookies for the current page

$page->navigate('http://example.com')->waitForNavigation();
$cookies = $page->getCookies();

// filter cookies with name == 'foo'
$cookiesFoo = $cookies->filterBy('name', 'foo'); 

// find first cookie with name == 'bar'
$cookieBar = $cookies->findOneBy('name', 'bar');
if ($cookieBar) {
    // do something
}
```

### Set user agent

You can set an user agent per page :

```php
$page->setUserAgent('my user agent');
```

See also BrowserFactory option ``userAgent`` to set it for the whole browser.


Advanced usage
--------------

The library ships with tools that hide all the communication logic but you can use the tools used internally to
communicate directly with chrome debug protocol.

Example:

```php
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Communication\Message;

// chrome devtools uri
$webSocketUri = 'ws://127.0.0.1:9222/devtools/browser/xxx';

// create a connection
$connection = new Connection($webSocketUri);
$connection->connect();

// send method "Target.activateTarget"
$responseReader = $connection->sendMessage(new Message('Target.activateTarget', ['targetId' => 'xxx']));

// wait up to 1000ms for a response
$response = $responseReader->waitForResponse(1000);
```

### Create a session and send message to the target

```php
// given a target id
$targetId = 'yyy';

// create a session for this target (attachToTarget)
$session = $connection->createSession($targetId);

// send message to this target (Target.sendMessageToTarget)
$response = $session->sendMessageSync(new Message('Page.reload'));
```

### Debugging

You can ease the debugging by setting a delay before each operation is made:

```php
  $connection->setConnectionDelay(500); // wait for 500 ms between each operation to ease debugging
```

### Browser (standalone)

```php
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Browser;

// chrome devtools uri
$webSocketUri = 'ws://127.0.0.1:9222/devtools/browser/xxx';

// create connection given a web socket uri
$connection = new Connection($webSocketUri);
$connection->connect();

// create browser
$browser = new Browser($connection);
```

## Contributing

See [CONTRIBUTING.md](.github/CONTRIBUTING.md) for contribution details.

## Credits

Thanks to [puppeteer](https://github.com/GoogleChrome/puppeteer) that served as an inspiration.

## Authors

* **Soufiane Ghzal** - *Initial work* - [gsouf](https://github.com/gsouf)
* **Graham Campbell** - *Current maintainer* - [GrahamCampbell](https://github.com/GrahamCampbell)

See also the list of [contributors](https://github.com/chrome-php/headless-chromium-php/contributors) who participated in this project.

## License

This project is licensed under the [Fair License](./LICENSE).
