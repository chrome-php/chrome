# CHANGELOG

## x.x.x

> *20xx-xx-xx* (not released)

> Description
    
* Features:
  * none
* Bug fixes:
  * none

## 0.4.0

> *2018-10-25*

> Ability to take full page screenshots
    
* Features:
  * Added ``Page::getLayoutMetrics`` that allows to take full page screenshots (#43 #44) thanks @kaero598
  * Added ``Page::getFullPageClip`` to ease full page screenshots
  
## 0.3.0

> *2018-10-13*

> Make a crawl instance sharable among multiple scripts

* Features:
  * Added option ``keepAlive`` for browser factory.
  * Added methods ``BrowserProcess::getSocketUri`` and ``ProcessAwareBrowser::getSocketUri``
  * Removed unused option ``debug``
  * Added ``BrowserFactory::connectToBrowser``
* Bug fixes:
  * (BC Break) Page navigation now allows by default that the initial loader is replaced with a new one #40

## 0.2.4

> *2018-10-04*
    
* Bug fixes:
  * Fixed a race condition in target creations/destruction (thanks @choval)

## 0.2.3

> *2018-10-02*

> Fixed usergent and added page prescript (thanks @tanasecosminromeo) and added some new options for browser factory
    
* Features:
  * Added method ``Browser::setPagePreScript``
  * Added method ``Page::addPreScript``
  * Added option ``"nosandbox"`` for browser factory
  * Added option ``"sendSyncDefaultTimeout"`` for browser factory
  * Added option ``"ignoreCertificateErrors"`` for browser factory
  * Added option ``"customFlags"`` for browser factory
* Bug fixes:
  * Fixed user agent string for browser factory 

## 0.2.2

> *2018-08-28*
    
* Features:
  * Added mouse api (move, click)
  * Page info are now in sync with the browser
  * Added a shortcut to get current page url: ``Page::getCurrentUrl``
  * Added ability to get and set cookies from a page: ``Page.setCookies``, ``Page.readCookies`` , ``Page.readAllCookies`` 
  * improved some error reporting
  * added ability to set custom user agent: ``Page::setUserAgent`` or via factory option ``userAgent``
* Bug fixes:
  * fixed a bug with directory creation for screenshots

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

