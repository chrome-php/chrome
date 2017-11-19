Chromium PHP
============

This library lets you start playing with chrome/chromium from PHP.

Install
-------

...

Usage
-----

Chromium PHP uses a simple and understandable API to start chrome, create pages, take screenshots, crawl websites...

```php
    use HeadlessChromium\BrowserFactory;

    $browserFactory = new BrowserFactory();

    // starts headless chrome
    $browser = $browserFactory->createBrowser();

    // creates a new page and navigate to an url
    $page = $browser->createPage();
    $page->navigate('http://example.com');
```


### Debugging 

The following example disables headless mode and intentionally slows chrome operations to help debugging

```php
    use HeadlessChromium\BrowserFactory;

    $browserFactory = new BrowserFactory();

    $browser = $browserFactory->createBrowser([
        'headless'        => false,      // disable headless mode
        'connectionDelay' => 1           // add 1 second delay between each operation to chrome
    ]);
```

### define the chrome executable

By default we assume that chrome will run with the commande ``chrome`` but you can change the executable:

```php
    use HeadlessChromium\BrowserFactory;

    // replace 'chrome' with 'chromium-browser'
    $browserFactory = new BrowserFactory('chromium-browser');
```


API
---

### Browser API




### Page API

#### Navigate to an url

```php
    $page->navigate('http://example.com');
```

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
