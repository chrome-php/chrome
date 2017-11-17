HEADLESS CHROMIUM PHP
=====================


Control headless chrome from PHP.




Advanced usage
--------------

The library ships with tools that wrap all the communication logic but you can use the tools used internally to 
communicate directly with chrome devtool protocol.

Example:

```php
  use HeadlessChromium\Communication\Connection;
  use HeadlessChromium\Communication\Message;

  // replace this with your actual uri
  $webSocketUri = 'ws://127.0.0.1:9222/devtools/browser/xxx';

  // create a connection
  $channel = new Connection($webSocketUri);
  $channel->connect();
  
  // send method "Target.activateTarget"
  $responseReader = $channel->sendMessage(New Message('Target.activateTarget', ['targetId' => 'xxx']));
  
  // wait up to 1000ms for a response
  $response = $responseReader->waitForResponse(1000);
  
  if ($response) {
    // ok
  }else {
    // not ok
  }
```
