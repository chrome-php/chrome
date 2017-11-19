CHROMIUM PHP
============

Control (headless) chrome from PHP.

Browser (standalone)
--------------------

Create a browser and navigate to a page

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

    // open a page
    $page = $browser->createPage('http://example.com');

    // see page doc for further examples for page
```

Page API
--------

**Navigate to an url**

```php
    $page->navigate('http://example.com');
```

Debug mode
----------

You can ease the debugging by setting a delay before each operation is made:

```php
  $connection->setConnectionDelay(500); // wait for 500 ms between each operation to ease debugging
```


Advanced usage
--------------

The library ships with tools that hide all the communication logic but you can use the tools used internally to
communicate directly with chrome devtool protocol.

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

Create a session and send message to the target:

```php
  // given a target id
  $targetId = 'yyy';

  // create a session for this target (attachToTarget)
  $session = $connection->createSession($targetId);

  // send message to this target (Target.sendMessageToTarget)
  $response = $session->sendMessageSync(new Message('Page.reload'));
```