# CHANGELOG


## 1.8.1 (2023-03-11)

* Fixed issue where Chrome 111 and later have different default allowed origins


## 1.8.0 (2023-02-27)

* Add helper function to find target
* Add `--crash-dumps-dir` option
* Allow passing the event name to wait for in `setHtml`
* Dropped PHP 7.3 support


## 1.7.2 (2023-02-27)

* Fix case where the timeout in `setHtml()` isn't respected
* Fix hard coded timeout in `Dom` class
* Fix hard coded timeout in `ResponseReader` class
* Stop process when an error occurs during startup
* Stop process on `waitForStartup` if dev tools failed to start
* Disconnect socket on `sendCloseMessage`


## 1.7.1 (2022-09-04)

* Fix command execution in `AutoDiscover`


## 1.7.0 (2022-08-28)

* Use `command` to guess the chrome executable in Linux
* Officially support PHP 8.2
* Fix extra HTTP headers


## 1.6.2 (2022-08-28)

* Fix intermittency in `Mouse::findElement()`
* Fix scroll with values higher than possible


## 1.6.1 (2022-05-17)

* Support Monolog 3


## 1.6.0 (2022-03-30)

* Officially support PHP 8.1


## 1.5.0 (2022-03-25)

* Added `Browser::getPages` method
* Added `Page::waitUntilContainsElement()` method
* Added `Page::setHtml()` method
* Added support for XPath by introducing `Selector`
* Added `Mouse::findElement()` method
* Switch to flatten mode


## 1.4.1 (2022-03-25)

* Added fallback to css layout metrics
* Added missing destroyed setting
* Prevent `Node::querySelector` from returning nodeId `0`
* Fixed "What's new" page opening on startup
* More fixes to enable eventual PHP 8.1 support


## 1.4.0 (2022-01-23)

* Added support for `--no-proxy-server` and `--proxy-bypass-list`
* Added timeout option to `Page::getHtml`
* Added `Node::sendFiles` method


## 1.3.1 (2022-01-23)

* Fixed issues with `Keyboard::typeText` with multibyte strings
* Fixed issues with retina and scaled displays
* Fixed issues with timeouts if system time changes
* Fixed `Mouse::find()` after cursor has moved


## 1.3.0 (2021-12-07)

* Added support for setting HTTP headers
* Added support for `psr/log` 2 and 3


## 1.2.1 (2021-12-07)

* Partial PHP 8.1 support


## 1.2.0 (2021-11-20)

* Dropped `--disable-default-apps` and `--disable-extensions` by default
* Added API for interacting with the DOM
* Added a way to set the timezone
* Reworked `PagePdf` class to improve validation


## 1.1.1 (2021-11-20)

* Fix mouse element position


## 1.1.0 (2021-09-26)

* Add DOM element locator


## 1.0.1 (2021-09-01)

* Fix mouse scroll maximum distance


## 1.0.0 (2021-08-15)

* Switched over to `chrome-php/wrench`
* Add support for keyboard key combinations


## 0.11.1 (2021-08-15)

* Fix scroll method returning prematurely


## 0.11.0 (2021-07-18)

* Added support for proxy servers as a direct option
* Added support for passing environment variables
* Added support for Symfony 6
* Removed broken `getChromeVersion` function
* Implemented more robust auto-discovery


## 0.10.0 (2021-05-22)

* Added `Page::getHtml`
* Added keyboard API
* Added mouse scrolling
* Attempt to auto-detect chrome binary path
* Added support for `setDownloadPath`
* Added support for `captureBeyondViewport`


## 0.9.0 (2020-12-09)

* Support PHP 8.0
* Increase default sync timeout to 5 seconds
* Set `--font-render-hinting=none` in headless mode
* Fixed keep alive option
* Fixed various phpdoc issues
* Fixed sending params to newer Chrome
* Fixed `Wrench::connect()` return value
* Avoid non-thread-safe getenv function


## 0.8.1 (2020-02-20)

* Fixed issues with `Browser::close`
* Support PHP 7.3 and 7.4


## 0.8.0 (2020-02-20)

* Added `Page::pdf`
* Added timeout for PageEvaluation methods
* Added support for Symfony 5
* Added `Browser::close`


## 0.7.0 (2019-10-04)

* Escaping custom flags for `BrowserFactory` is now automatic
* Added timeout for `Page::getFullPageClip`
* Added timeout for method `getBase64`
* Added options `headerTemplate` and `footerTempalte` for `Page::pdf`
* Added options `scale` for Page::pdf
* Handle gracefully all pages failing to close
* Fixed deprecation from Symfony
