Chromium PHP
============

[![Build Status](https://travis-ci.org/gsouf/headless-chromium-php.svg?branch=master)](https://travis-ci.org/gsouf/headless-chromium-php)
[![Test Coverage](https://codeclimate.com/github/gsouf/headless-chromium-php/badges/coverage.svg)](https://codeclimate.com/github/gsouf/headless-chromium-php/coverage)
[![Latest Stable Version](https://poser.pugx.org/gsouf/chromium/version)](https://packagist.org/packages/gsouf/chromium)
[![License](https://poser.pugx.org/gsouf/chromium/license)](https://packagist.org/packages/gsouf/chromium)

This library lets you start playing with chrome/chromium in headless mode from PHP.

> **/!\\** The library is currently at a very early stage. You are enouraged to play with it but keepin mind that it is still very young and still lacks most of the features you would expect.


Requirements
------------

Requires php 7 and a chrome/chromium exacutable. 

As of version 65 of chrome/chromium the library proved to work correctly. There are known bug prior to version 63
that the library doesn't plan to had support for.

Note that the library is only tested on linux.

Install
-------

The library can be installed with composer and is available on packagist under [gsouf/chromium](https://packagist.org/packages/gsouf/chromium)

``composer require gsouf/chromium``

Usage
-----

Chromium PHP uses a simple and understandable API to start chrome, to open pages, to take screenshots, 
to crawl websites... and almost everything that you can do with chrome as a human.

```php
    use HeadlessChromium\BrowserFactory;

    $browserFactory = new BrowserFactory();

    // starts headless chrome
    $browser = $browserFactory->createBrowser();

    // creates a new page and navigate to an url
    $page = $browser->createPage();
    $page->navigate('http://example.com');
    
    $browser->close();
```


### Debugging 

The following example adds some development-oriented features to help debugging 

```php
    use HeadlessChromium\BrowserFactory;

    $browserFactory = new BrowserFactory();

    $browser = $browserFactory->createBrowser([
        'headless'        => false,         // disable headless mode
        'connectionDelay' => 0.8,           // add 0.8 second of delay between each instruction sent to chrome,
        'debugLogger'     => 'php://stdout' // will enable verbose mode
    ]);
```

About ``debugLogger``: this can be any of a resource string, a resource or an object implementing ``LoggerInterface`` from Psr\Log (such as [monolog](https://github.com/Seldaek/monolog) or [apix/log](https://github.com/apix/log)).


### Using different chrome executable

By default we assume that chrome will run with the commande ``chrome`` but you can change the executable:

```php
    use HeadlessChromium\BrowserFactory;

    // replace default 'chrome' with 'chromium-browser'
    $browserFactory = new BrowserFactory('chromium-browser');
```


------------------------------------------------------------------------------------------------------------------------



API
---

### Browser Factory

#### Options

Here are the options available for the browser factory:

| Option name        | Default               | Description                                                                     |
|--------------------|-----------------------|---------------------------------------------------------------------------------|
| connectionDelay    | 0                     | Delay to apply between each operation for debugging purposes                    |
| debugLogger        | null                  | A string (e.g "php://stdout"), or resource, or PSR-3 logger instance to print debug messages |
| headless           | true                  | Enable or disable headless mode                                                 |
| startupTimeout     | 30                    | Maximum time in seconds to wait for chrome to start                             |
| userDataDir        | none                  | chrome user data dir (default: a new empty dir is generated temporarily)        |
| enableImages       | true                  | Toggles loading of images |

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
    $navigation->waitForNavigation(Page::DOM_CONTENT_LOADED, 10000)
```

#### Evaluate script on the page

Once the page has completed the navigation you can evaluate arbitrary script on this page. 
Full example with waitForNavigation:

```php
    // navigate
    $navigation = $page->navigate('http://example.com');
        
    // wait for the page to be loaded
    $navigation->waitForNavigation();
    
    $page->evaluate('document.documentElement.innerHTML');
```



------------------------------------------------------------------------------------------------------------------------



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

  if ($response) {
    // ok
  }else {
    // not ok
  }
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

See [CONTRIBUTING.md](./CONTRIBUTING.md) for contribution details.

## Credits

Thanks to [puppeteer](https://github.com/GoogleChrome/puppeteer) that served as an inspiration.

## Roadmap

- Improve browser shutdown (check for chrome 63)
- Dispatch events sent by chrome and allow async-like mode
- Interact with DOM/javascript
- Make screenshots
- Make pdf
- Create a DOM manipulation framework
- Inspect network traces
- Emulate hardware (mouse/keyboard)
- Adding api documentation (https://github.com/victorjonsson/PHP-Markdown-Documentation-Generator/blob/master/docs.md)

## Authors

* **Soufiane Ghzal** - *Initial work* - [gsouf](https://github.com/gsouf)

See also the list of [contributors](https://github.com/gsouf/headless-chromium-php/contributors) who participated in this project.

## License

This project is licensed under the [Fair License](./LICENSE).
