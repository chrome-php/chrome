# CHANGELOG

## x.x.x

> *20xx-xx-xx* (not released)

> Description
    
* Features:
  * none
* Bug fixes:
  * none


## 0.2.2

> *2018-08-28*
    
* Features:
  * Added mouse api (move, click)
  * Page info are now in sync with the browser
  * Added a shortcut to get current page url: ``Page::getCurrentUrl``
  * Added ability to get and set cookies from a page: ``Page.setCookies``, ``Page.readCookies`` , ``Page.readAllCookies`` 
  * improved some error reporting
  * fixed a bug with directory creation for screenshots
  * added ability to set custom user agent: ``Page::setUserAgent`` or via factory option ``userAgent``
* Bug fixes:
  * none

## 0.2.1

> *2018-06-20*

> Make viewport and window's size customizable
    
* Features:
  * Added option ``windowSize`` in BrowserFactory
  * Added methods ``Page::setViewportSize`` and ``Page::setDeviceMetricsOverride``

## 0.2.0

> *2018-06-15*

> Description
    
* Features:
  * Add constant Page::NETWORK_IDLE
* Bug fixes:
  * Make connection reader to be more atomic in order to read messages and events in the order they come in
  * Make Page::navigate()->waitForNavigation (#20)
  
--------------

## 0.1.4

> *2018-04-27*

> Description

* Features:
  * Add Page::close

--------------

## 0.1.3

> *2018-04-26*

> Description
    
* Features:
  * Add PageEvaluation::waitForPageReload
  
--------------


## 0.1.2

> *2018-04-26*

> Description
    
* Features:
  * Improved startup error message
* Bug fixes:
  * Fixed bugs on shutdown
  * Fixed unit tests
  * Allow CHROME_PATH to have spaces in the path
  
--------------

## 0.1.1

> *2018-04-25*

> Description
    
* Features:
  * **BC BREAK** try to start chrome using env variable ``CHROME_PATH`` before using default ``"chrome"``.

