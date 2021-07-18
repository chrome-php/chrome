# CHANGELOG


## 0.11.0 (2021-07-18)

* Added support for proxy servers as a direct option
* Added support for passing environment variables
* Added support for MacOS auto-discovery
* Added support for Symfony 6
* Removed broken `getChromeVersion` function


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

* Fixed issued with `Browser::close`
* Tested on PHP 7.3 and 7.4


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
